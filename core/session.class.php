<?php

namespace Core;

class Session {
    private $session;
    protected $request;
    protected $user = null;
    protected $headers = array();
    
    const SESS_KEY = "session_var";
    
    public function __construct(Request $request, &$session = false) {
        $this->request = $request;
        // We need a reference to the global $_SESSION object so it can store state correctly
        // Assigning by reference doesn't work with ternary operators
        if ($session) {
            $this->session =& $session;
        } else {
            $this->session =& $_SESSION;
        }
        
        $this->setCacheHeaders();
        
        session_name(self::SESS_KEY);
    }
    protected function setCacheHeaders () {
        if ($this->isLoggedIn()) {
            $this->nocache();
        } else {
            $this->cache();
        }
    }
    protected function startSession () {
        if (!$this->isStarted()) {
            if ($this->session === false) {
                $this->session = array();
            } else {
                session_start();
                $this->session =& $_SESSION;
                // Set the headers again cos session_start() overrides them with session_cache_limiter()
                $this->nocache();
            }
        }
    }
    protected function endSession () {
        $expires = time() - 60*60*24*365;
        $params = session_get_cookie_params();
        // TODO use response class
        setcookie(session_name(), '', $expires, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        if ($this->isStarted()) {
            session_destroy();
        }
    }
    public function isStarted () {
        return is_array($this->session);
    }
    public function hasCookie () {
        return $this->request->hasCookie(session_name());
    }
    public function set ($key, $value) {
        $this->startSession();
        $this->session[$key] = $value;
    }
    public function get ($key, $default = false) {
        $this->startSession();
        return array_key_exists($key, $this->session) ? $this->session[$key] : $default;
    }
    public function delete ($key) {
        $this->startSession();
        $value = $this->get($key);
        $this->set($key, null);
        unset($this->session[$key]);
        return $value;
    }
    protected function setHeader ($name, $value) {
        $this->headers[$name] = $value;
    }
    protected function setHeaders ($headers) {
        $this->headers = array_merge($this->headers, $headers);
    }
    public function getHeaders () {
        return $this->headers;
    }
    public function nocache () {
        $expires = -60*60*24*365;
        $this->setHeaders(array(
            'Expires'       => gmdate("D, d M Y H:i:s", time() + $expires) . ' GMT',
            'Cache-control' => 'no-cache, must-revalidate',
            'Pragma'        => 'no-cache',
        ));
    }
    public function cache () {
        $expires = 60*60*24*30;
        $this->setHeaders(array(
            'Expires'       => gmdate("D, d M Y H:i:s", time() + $expires) . ' GMT',
            'Cache-control' => 'public, max-age=' . $expires,
            'Vary'          => 'cookie',
        ));
    }
    public function getUser() {
        return $this->user;
    }
    public function isLoggedIn() {
        return (bool) $this->getUser();
    }
}
