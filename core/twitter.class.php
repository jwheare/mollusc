<?php

namespace Core;
use Exception;

class Twitter {
    
    const KEY = TWITTER_KEY;
    const SECRET = TWITTER_SECRET;
    
    const API_ROOT = 'http://api.twitter.com';
    const API_VERSION = 1;
    
    var $token = null;
    var $secret = null;
    
    var $followLocation = true;
    var $multipart = false;
    
    public function __construct($token = null, $secret = null) {
        if ($token && $secret) {
            $this->setCredentials($token, $secret);
        }
    }
    /**
     * API method helpers
    **/
    protected function callUrl($url, $method, $callParams = array(), $forceAuth = false) {
        $headers = array(
            // http://groups.google.com/group/twitter-development-talk/browse_thread/thread/3c859b7774b1e95d
            'X-Twitter-Content-Type-Accept' => 'application/x-www-form-urlencoded',
        );
        if ($this->token || $forceAuth) {
            $headers['Authorization'] = $this->buildAuthorizationHeader($url, $method, $callParams);
        }
        try {
            $request = new HttpRequest();
            $request->multipart = $this->multipart;
            $request->followLocation = $this->followLocation;
            
            $formattedHeaders = array();
            foreach ($headers as $k => $v) {
                $formattedHeaders[] = "$k: $v";
            }
            list($response, $httpInfo) = $request->send($url, $method, $callParams, $formattedHeaders);
        } catch (HTTPRequestException $e) {
            switch ($e->getHttpCode()) {
            case 400:
                $message = "Invalid Twitter request";
                break;
            case 401:
                $message = "Unauthorised Twitter request";
                break;
            case 403:
                $message = "Twitter refused this request";
                break;
            case 413:
                $message = "Twitter request too large";
                break;
            case 500:
                $message = 'Twitter’s having server troubles. Check their status: http://status.twitter.com/';
                break;
            case 502:
                $message = "Twitter may be down or undergoing an upgrade, or this was just a slow query. Try again later";
                break;
            case 503:
                $message = "Twitter’s temporarily overloaded. Try again";
                break;
            default:
                $message = "Twitter request failed";
            }
            if ($eMessage = $e->getMessage()) {
                $message .= " - $eMessage";
            }
            throw new TwitterException($message, $e->getCode(), $e->getMethod(), $e->getUrl(), $callParams, $headers, $e->getResponse(), $e->getResponseHeaders(), $e->getHttpError());
        }
        return array($response, $httpInfo);
    }
    protected function buildUrl($path, $versioned = true) {
        $baseUrl = self::API_ROOT;
        if ($versioned) {
            $baseUrl .= '/' . self::API_VERSION;
        }
        return "$baseUrl/$path";
    }
    protected function parseBodyString($body) {
        $params = array();
        parse_str($body, $params);
        return $params;
    }
    
    public function setCredentials($token, $secret) {
        $this->token = $token;
        $this->secret = $secret;
    }
    
    /**
     * Public API methods
    **/
    public function head($url, $params = array(), $forceAuth = false) {
        list($response, $httpInfo) = $this->callUrl($this->buildUrl($url), 'HEAD', $params, $forceAuth);
        return $httpInfo;
    }
    public function get($url, $params = array(), $forceAuth = false) {
        list($response, $httpInfo) = $this->callUrl($this->buildUrl($url), 'GET', $params, $forceAuth);
        return json_decode($response);
    }
    public function post($url, $params = array(), $forceAuth = false) {
        list($response, $httpInfo) = $this->callUrl($this->buildUrl($url), 'POST', $params, $forceAuth);
        return json_decode($response);
    }
    // http://dev.twitter.com/doc/get/users/profile_image/:screen_name
    public function getProfileImage($screenName) {
        $url = $this->buildUrl("users/profile_image/{$screenName}.json");
        $method = 'HEAD';
        $this->followLocation = false;
        list($response, $httpInfo) = $this->callUrl($url, $method, array());
        $this->followLocation = true;
        if (!isset($httpInfo['location_header'])) {
            throw new TwitterException('Missing location header in Twitter response', 502, $method, $url, null, null, '', $httpInfo['response_headers'][$url]);
        }
        return $httpInfo['location_header'];
    }
    // http://dev.twitter.com/doc/get/users/show
    public function getProfileInfo($userId) {
        return $this->get('users/show.json', array(
            'user_id' => $userId,
        ));
    }
    public function getProfileInfoFromName($screenName) {
        return $this->get('users/show.json', array(
            'screen_name' => $screenName,
        ));
    }
    public function lookupProfileInfoFromNames($screenNames) {
        return $this->get('users/lookup.json', array(
            'screen_name' => implode(',', $screenNames),
        ));
    }
    public function lookupProfileInfo($userIds) {
        return $this->get('users/lookup.json', array(
            'user_id' => implode(',', $userIds),
        ));
    }
    // http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-followers%C2%A0ids
    public function getFollowers($userId, $cursor = '-1') {
        return $this->get('followers/ids.json', array(
            'user_id' => $userId,
            'cursor' => $cursor,
        ));
    }
    public function updateProfileImage ($fileName, $mimeType = null) {
        $this->multipart = true;
        $curlFilePath = "@$fileName";
        if ($mimeType) {
            $curlFilePath .= ";type=$mimeType";
        }
        $result = $this->post('account/update_profile_image.json', array(
            'image' => $curlFilePath,
        ));
        $this->multipart = false;
        return $result;
    }
    public function updateStatus($status, $reply = null, $place = null, Point $point = null) {
        $params = array(
            'status' => $status,
        );
        if ($reply) {
            $params['in_reply_to_status_id'] = $reply;
        }
        if ($place) {
            $params['place_id'] = $place;
        } else if ($point && $point->latitude && $point->longitude) {
            $params['lat'] = $point->latitude;
            $params['long'] = $point->longitude;
        }
        return $this->post('statuses/update.json', $params);
    }
    public function retweet($id) {
        return $this->post("statuses/retweet/$id.json");
    }
    public function userTimeline ($userId, $sinceId = null, $maxId = null, $count = null) {
        $params = array(
            'user_id' => $userId,
            'trim_user' => true,
            'count' => $count,
            'include_rts' => true,
        );
        if ($sinceId) {
            $params['since_id'] = $sinceId;
        }
        if ($maxId) {
            $params['max_id'] = $maxId;
        }
        return $this->get('statuses/user_timeline.json', $params);
    }
    // http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-friends%C2%A0ids
    public function getFriends($userId, $cursor = '-1') {
        return $this->get('friends/ids.json', array(
            'user_id' => $userId,
            'cursor' => $cursor,
        ));
    }
    // oAuth Step 1 - Get request token
    // http://oauth.net/core/1.0a/#auth_step1
    // http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-oauth-request_token
    public function getRequestToken(Request $request, $callback = null) {
        $url = $this->buildUrl('oauth/request_token', false);
        $method = 'POST';
        $params = array();
        $requiredParams = array(
            'oauth_token',
            'oauth_token_secret',
        );
        if ($callback) {
            $params['oauth_callback'] = Url::addHost($callback);
            $requiredParams[] = 'oauth_callback_confirmed';
        }
        list($response, $httpInfo) = $this->callUrl($url, $method, $params, true);
        $requestTokenParams = $this->parseBodyString($response);
        if (empty($requestTokenParams)) {
            throw new TwitterException('Missing parameters in Twitter response', 502, $method, $url, $params, null, $response);
        }
        foreach ($requiredParams as $param) {
            if (!isset($requestTokenParams[$param])) {
                throw new TwitterException("Missing $param in Twitter response", 502, $method, $url, $params, null, $response);
            }
        }
        return $requestTokenParams;
    }
    
    // oAuth Step 2 - Redirect to auth URL
    // http://oauth.net/core/1.0a/#auth_step2
    public function getAuthorizationUrl($forceLogin = false) {
        // http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-oauth-authenticate
        $params = array(
            'oauth_token' => $this->token,
        );
        if ($forceLogin) {
            $params['force_login'] = 'true';
        }
        $url = Url::build($this->buildUrl('oauth/authenticate', false), $params);
        return $url;
    }
    
    // oAuth Step 3 - Exchange request token stored in the session for an oAuth token and secret.
    // http://oauth.net/core/1.0a/#auth_step3
    public function getAccessToken($authTokenVerifier) {
        // http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-oauth-access_token
        $url = $this->buildUrl('oauth/access_token', false);
        $method = 'POST';
        list($response, $httpInfo) = $this->callUrl($url, $method, array(
            'oauth_verifier' => $authTokenVerifier,
        ));
        $accessTokenParams = $this->parseBodyString($response);
        if (empty($accessTokenParams)) {
            throw new TwitterException('Missing parameters in Twitter response', 400, $method, $url, null, null, $response);
        }
        return $accessTokenParams;
    }
    
    public function verifyCredentials() {
        // http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-account%C2%A0verify_credentials
        return $this->get('account/verify_credentials.json');
    }
    
    /**
     * Auth params
    **/
    
    // http://oauth.net/core/1.0a/#auth_header
    protected function buildAuthorizationHeader($url, $method, $callParams = array()) {
        // Merge in extra params
        $authParams = $this->getOAuthParams();
        // Sign
        $sigParams = $authParams;
        if (!$this->multipart) {
            $sigParams = array_merge($sigParams, $callParams);
        }
        $signature = $this->generateSignature($sigParams, $url, $method);
        $authParams['oauth_signature'] = $signature;
        // Encode pairs
        $encodedPairs = Url::encodePairs($authParams, true);
        // Write the header
        $header = 'OAuth realm="' . $url . '", '
               . implode(', ', $encodedPairs);
        return $header;
    }
    // Common OAuth params needed for every request
    protected function getOAuthParams() {
        $params = array(
            'oauth_consumer_key'     => self::KEY,
            'oauth_signature_method' => "HMAC-SHA1",
            'oauth_timestamp'        => time(),
            'oauth_nonce'            => md5(uniqid(mt_rand(), true)),
            'oauth_version'          => "1.0",
            'oauth_token'            => $this->token,
        );
        return $params;
    }
    
    /**
     * Signing
     * http://oauth.net/core/1.0a/#signing_process
    **/
    protected function generateSignature($params, $url, $method) {
        // Base string = [METHOD][url][normalised pairs]
        $baseParts = array(
            Url::encodeRFC3986(strtoupper($method)),
            Url::encodeRFC3986($url),
            Url::encodeRFC3986(Url::normaliseParams($params)),
        );
        $baseString = implode('&', $baseParts);
        // Sign with HMAC-SHA1
        $key = $this->buildSigningKey();
        $signature = base64_encode(hash_hmac('sha1', $baseString, $key, true));
        // echo("baseString: $baseString\nkey: $key\nsignature: $signature\n==============\n");
        return $signature;
    }
    // Uses a saved request/access token secret if there is one
    protected function buildSigningKey() {
        $key = Url::encodeRFC3986(self::SECRET) . '&' . Url::encodeRFC3986($this->secret);
        return $key;
    }
}

class TwitterException extends Exception {
    var $method;
    var $url;
    var $params;
    var $headers;
    var $response;
    var $responseHeaders;
    var $previous;
    public function __construct($message, $code = 0, $method = '', $url = '', $params = array(), $headers = array(), $response = '', $responseHeaders = array(), Exception $previous = null) {
        $this->method = $method;
        $this->url = $url;
        $this->params = $params;
        $this->headers = $headers;
        $this->response = $response;
        $this->responseHeaders = $responseHeaders;
        parent::__construct($message, $code, $previous);
    }
    
    public function __toString() {
        return get_called_class() . " {$this->getStatusLine()}: {$this->method} {$this->url}";
    }
    public function getStatusLine() {
        return "{$this->getCode()} {$this->getMessage()}";
    }
    public function parseError () {
        if (is_object($response = json_decode($this->response))) {
            if (isset($response->errors)) {
                return $response->errors;
            }
            if (isset($response->error)) {
                return $response->error;
            }
        }
    }
    public function debugString () {
        $str = "{$this->method} {$this->url}\n";
        if ($this->params) {
            $maxKeyLength = max(array_map('strlen', array_keys($this->params)));
            foreach ($this->params as $k => $v) {
                $str .= sprintf("%s %-{$maxKeyLength}s => [%s]\n", str_repeat(' ', strlen($this->method)), $k, $v);
            }
        }
        if ($error = $this->parseError()) {
            $str .= "\n$error\n";
        }
        if ($this->headers) {
            $str .= "\nRequest Headers";
            $str .= "\n---------------\n";
            $maxKeyLength = max(array_map('strlen', array_keys($this->headers)));
            foreach ($this->headers as $k => $v) {
                $str .= sprintf("%-{$maxKeyLength}s => %s\n", $k, $v);
            }
        }
        $str .= "\n{$this->getStatusLine()}\n";
        if ($this->responseHeaders) {
            $str .= "\nResponse Headers";
            $str .= "\n----------------\n";
            $maxKeyLength = max(array_map('strlen', array_keys($this->responseHeaders)));
            foreach ($this->responseHeaders as $k => $v) {
                $str .= sprintf("%-{$maxKeyLength}s => %s\n", $k, $v);
            }
        }
        $str .= "\n{$this->response}\n";
        return $str;
    }
}
