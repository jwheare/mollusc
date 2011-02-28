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
        $dispatcher = new Dispatcher($request, array_merge(App\Route::getPatterns(), self::getPatterns()));
        
        // Encapsulate the user session
        $session = new Session($request);
        $request->setSession($session);
        
        // Process the request
        $response = $dispatcher->processRequest($request);
        $response->setHeaders($session->getHeaders());
        // Render the response
        $response->respond();
    }
}
