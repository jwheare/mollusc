<?php

namespace Core;

class Request {
    protected $url;
    protected $urlParts = array();
    protected $session;
    private $server = array();
    private $cookie = array();
    private $get = array();
    private $post = array();
    private $put = array();
    private $delete = array();
    /**
     * Parse input variables
    **/
    public function __construct ($server = null, $input = null, $cookie = null) {
        $this->server = $server ? $server : $_SERVER;
        $this->cookie = $cookie ? $cookie : $_COOKIE;
        $this->url = $this->server('request_uri');
        $this->urlParts = Url::extractUrlParts($this->url);
        // Parse GET variables
        if (isset($this->urlParts['queryparams'])) {
            $this->get = $this->urlParts['queryparams'];
        }
        // Parse non GET input variables
        $method = strtolower($this->getMethod());
        if (in_array($method, array('post', 'put', 'delete'))) {
            parse_str($input ? $input : file_get_contents("php://input"), $this->$method);
        }
    }
    public function setSession (Session $session) {
        $this->session = $session;
    }
    public function getSession () {
        return $this->session;
    }
    /**
     * Retrieve input variables and path args, with a fallback if not present
    **/
    public function server ($key, $default = null) {
        $key = strtoupper($key);
        return array_key_exists($key, $this->server) ? $this->server[$key] : $default;
    }
    public function hasCookie ($key) {
        return array_key_exists($key, $this->cookie);
    }
    public function cookie ($key, $default = null) {
        return $this->hasCookie($key) ? $this->cookie[$key] : $default;
    }
    public function get ($key, $default = null) {
        return array_key_exists($key, $this->get) ? $this->get[$key] : $default;
    }
    public function post ($key, $default = null) {
        return array_key_exists($key, $this->post) ? $this->post[$key] : $default;
    }
    public function postget ($key, $default = null) {
        return $this->post($key, $this->get($key, $default));
    }
    public function put ($key, $default = null) {
        return array_key_exists($key, $this->put) ? $this->put[$key] : $default;
    }
    public function delete ($key, $default = null) {
        return array_key_exists($key, $this->delete) ? $this->delete[$key] : $default;
    }
    
    /**
     * Retrieve an array of mime/types specified in the Accept header, ordered
     * by preference
     * Returns null if header is missing
     * 
     * @return array | null
    **/
    public function getAcceptableMimeTypes () {
        $accept = $this->server('http_accept');
        if (!$accept) {
            return null;
        }
        // Values will be stored in this array
        $acceptTypes = array();
        // Accept header is case insensitive, and whitespace isn’t important
        $accept = strtolower(str_replace(' ', '', $accept));
        // divide it into parts in the place of a ","
        $acceptParts = explode(',', $accept);
        foreach ($acceptParts as $part) {
            // the default quality is 1.
            $q = 1;
            // check if there is a different quality
            if (strpos($part, ';q=')) {
                // divide "mime/type;q=X" into two parts: "mime/type" i "X"
                list($part, $q) = explode(';q=', $part);
            }
            // mime-type $part is accepted with the quality $q
            // WARNING: $q == 0 means, that mime-type isn’t supported!
            $acceptTypes[$part] = (float) $q;
        }
        arsort($acceptTypes);
        return $acceptTypes;
    }
    
    /**
     * Should this request prioritise JSON response
     *
     * @return bool
    **/
    public function acceptJson () {
        $acceptableMimeTypes = $this->getAcceptableMimeTypes();
        if ($acceptableMimeTypes && isset($acceptableMimeTypes["application/json"])) {
            $jsonQ = $acceptableMimeTypes["application/json"];
            foreach ($acceptableMimeTypes as $mime => $q) {
                if ($q > $jsonQ) {
                    return false;
                }
                if ($q === $jsonQ && $mime == "text/html") {
                    return false;
                }
            }
            return true;
        }
        return false;
    }
    public function getUrl () {
        return $this->url;
    }
    public function getUrlPart ($key, $default = null) {
        return array_key_exists($key, $this->urlParts) ? $this->urlParts[$key] : $default;
    }
    public function getMethod () {
        return $this->server('request_method');
    }
    public function shouldUncache () {
        return $this->server('http_cache_control') == 'max-age=0';
    }
}
