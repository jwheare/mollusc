<?php

namespace Core;
use Core\HttpStatus;
use Core\HttpRequestException;

class HttpRequest {
    static $lastRequestInfo;
    protected $curl;
    protected $response_headers = array();
    protected $cookies = array();
    protected $cookieString = '';
    
    public $followLocation;
    public $multipart;
    
    public function __construct() {
        $curl = curl_init();
        /* Curl settings */
        curl_setopt_array($curl, array(
            CURLOPT_USERAGENT      => SITE_NAME . ' | ' . HOST_NAME,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_AUTOREFERER    => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_COOKIEJAR      => '/dev/null',
            CURLOPT_HEADER         => false,
            CURLINFO_HEADER_OUT    => true,
            CURLOPT_HEADERFUNCTION => array($this, 'getHeader')
        ));
        $this->curl = $curl;
    }
    protected function setHeaders($headers = array()) {
        // Filter blanks
        array_filter($headers);
        // Don't cache
        $headers[] = 'Cache-Control: no-cache, max-age=0';
        
        // This causes trouble with the Twitter API
        // Expect: 100-Continue
        // http://matthom.com/archive/2008/12/29/php-curl-disable-100-continue-expectation
        // http://groups.google.com/group/twitter-development-talk/browse_thread/thread/7c67ff1a2407dee7
        $headers[] = 'Expect:';
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
    }
    
    protected function prepareGet($url, $requestParams = array()) {
        $url = Url::build($url, $requestParams);
        curl_setopt_array($this->curl, array(
            CURLOPT_HTTPGET    => true,
        ));
        return $url;
    }
    protected function prepareHead($url, $requestParams = array()) {
        $url = $this->prepareGet($url, $requestParams);
        curl_setopt($this->curl, CURLOPT_NOBODY, true);
        return $url;
    }
    protected function preparePost($requestParams = array()) {
        curl_setopt_array($this->curl, array(
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => $this->processPostFields($requestParams),
        ));
    }
    protected function processPostFields ($requestParams = array()) {
        if ($this->multipart) {
            return $requestParams;
        } else {
            return Url::encodePairsToString($requestParams);
        }
    }
    protected function preparePut($requestParams = array()) {
        curl_setopt_array($this->curl, array(
            CURLOPT_PUT        => true,
            CURLOPT_POSTFIELDS => $requestParams,
        ));
    }
    protected function prepareDelete($requestParams = array()) {
        curl_setopt_array($this->curl, array(
            CURLOPT_POSTFIELDS    => $requestParams,
        ));
    }
    public function send($url, $method = 'GET', $requestParams = array(), $headers = array()) {
        // Initialise curl 
        $this->setHeaders($headers);
        curl_setopt_array($this->curl, array(
            CURLOPT_FOLLOWLOCATION => $this->followLocation,
            CURLOPT_COOKIE         => $this->cookieString,
            CURLOPT_CUSTOMREQUEST  => $method,
        ));
        $this->response_headers = array();
        // Build the request
        switch ($method) {
        case 'POST':
            $this->preparePost($requestParams);
            break;
        case 'PUT':
            $this->preparePut($requestParams);
            break;
        case 'DELETE':
            $this->prepareDelete($requestParams);
            break;
        case 'HEAD':
            $url = $this->prepareHead($url, $requestParams);
            break;
        case 'GET':
            $url = $this->prepareGet($url, $requestParams);
            break;
        default:
            throw new HttpRequestException(HttpStatus\Base::mapCodeToStatus(501), $method, $url, $requestParams, '', array(), "The HttpRequest class doesn’t know how to make $method requests");
        }
        curl_setopt($this->curl, CURLOPT_URL, $url);
        // Send request response
        $response = curl_exec($this->curl);
        $httpInfo = curl_getinfo($this->curl);
        $httpInfo['response_headers'] = $this->response_headers;
        // echo("$response\n==============\nurl: $url\nparams: " . Url::encodePairsToString($requestParams) . "\n");
        // if (isset($httpInfo['request_header'])) {
        //     echo("==============\n{$httpInfo['request_header']}\n");
        // }
        self::$lastRequestInfo = $httpInfo;
        $httpCode = $httpInfo['http_code'];
        // Store cookies
        if (isset($this->response_headers[$url]['set_cookie'])) {
            $this->response_headers[$url]['set_cookie'] = (array) $this->response_headers[$url]['set_cookie'];
            foreach ($this->response_headers[$url]['set_cookie'] as $cookie) {
                $cookieParts = explode(';', $cookie);
                $cookieKV = explode('=', $cookieParts[0]);
                $this->cookies[$cookieKV[0]] = $cookieKV[1];
                $this->cookieString .= "{$cookieKV[0]}={$cookieKV[1]}; ";
            }
        }
        $httpInfo['cookies'] = $this->cookies;
        $httpInfo['cookieString'] = $this->cookieString;
        // Throw exception for errors
        if ($response === false) {
            // cURL error
            $curlError = "cURL error: " . curl_error($this->curl) . " (" . curl_errno($this->curl) . ")";
            throw new HttpRequestException(HttpStatus\Base::mapCodeToStatus(504), $method, $url, $requestParams, null, null, $curlError);
        }
        if (!$this->followLocation && $httpCode > 300 && $httpCode < 400) {
            // Helpfully extract the location header
            if (isset($this->response_headers[$url]) && isset($this->response_headers[$url]['location'])) {
                $httpInfo['location_header'] = $this->response_headers[$url]['location'];
            }
        } else if ($httpCode !== 200) {
            $message = '';
            if (!$httpErrorClass = HttpStatus\Base::mapCodeToStatus($httpCode)) {
                $httpErrorClass = HttpStatus\Base::mapCodeToStatus(502); // BadGateway
                $message = "Unhandled HTTP Error: $httpCode";
            }
            throw new HttpRequestException($httpErrorClass, $method, $url, $requestParams, $response, $this->response_headers[$url], $message);
        }
        // Close handle
        curl_close($this->curl);
        // Return response data
        return array($response, $httpInfo);
    }
    
    protected function getHeader($curl, $header) {
        // echo($header);
        $i = strpos($header, ':');
        if (!empty($i)) {
            $url = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
            if (!isset($this->response_headers[$url])) {
                $this->response_headers[$url] = array();
            }
            $key = str_replace('-', '_', strtolower(substr($header, 0, $i)));
            $value = trim(substr($header, $i + 2));
            if (isset($this->response_headers[$url][$key])) {
                $this->response_headers[$url][$key] = (array) $this->response_headers[$url][$key];
                $this->response_headers[$url][$key][] = $value;
            } else {
                $this->response_headers[$url][$key] = $value;
            }
        }
        return strlen($header);
    }
}
