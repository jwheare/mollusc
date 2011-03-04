<?php

namespace Core;
use App;

class Web {
    static function getPatterns () {
        return array(
            '/^(?P<controller>[^\/]*)\/?(?P<ACTION>.*)/' => '$controller',
        );
    }
    public function handleRequest () {
        // Encapsulate an HTTP request
        $request = new Request();
        // Don't show HTML errors for JSON
        if ($request->acceptJson()) {
            ini_set('html_errors', 0);
        }
        
        // Setup the dispatcher with App URL patterns
        $routes = self::getPatterns();
        if (class_exists('App\Route')) {
            $routes = array_merge(App\Route::getPatterns(), $routes);
        }
        $dispatcher = new Dispatcher($request, $routes);
        
        // Encapsulate the user session
        if (class_exists('App\Session')) {
            $session = new App\Session($request);
        } else {
            $session = new Session($request);
        }
        $request->setSession($session);
        
        // Process the request
        $response = $dispatcher->processRequest($request);
        $response->setHeaders($session->getHeaders());
        // Render the response
        $response->respond();
    }
}
