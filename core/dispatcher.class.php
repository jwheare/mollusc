<?php

namespace Core;
use Exception;

class Dispatcher {
    protected $routes;
    protected $request;
    
    public function __construct (Request $request, array $routes) {
        $this->request = $request;
        $this->routes = $routes;
    }
    protected function generateExceptionResponse (HttpStatus\Base $exception) {
        if ($exception instanceof HttpStatus\BaseRedirect) {
            $response = new Response\Redirect($this->request, $exception);
        } elseif ($this->request->acceptJson()) {
            $response = new Response\JsonError($this->request, $exception);
        } else {
            $response = new Response\HtmlError($this->request, $exception);
        }
        return $response;
    }
    /**
     * Map a request to controller parts
     *
     * @throws Core\HttpStatus
    **/
    public function mapRequestToControllerParts () {
        // Map request method / accept to controller type
        $method = $this->request->getMethod();
        switch ($method) {
        case 'GET':
        case 'HEAD':
            if ($this->request->acceptJson()) {
                $type = "json{$method}";
            } else {
                $type = "html";
            }
            break;
        case 'POST':
            if ($this->request->acceptJson()) {
                $type = "json{$method}";
            } else {
                $type = "form";
            }
            break;
        case 'PUT':
        case 'DELETE':
            if ($this->request->acceptJson()) {
                $type = "json{$method}";
            } else {
                // Only JSON accepts complex methods
                $type = "invalid";
            }
            break;
        default:
            throw new HttpStatus\NotImplemented("$method requests aren’t implemented on this server");
            break;
        }
        $type = strtolower($type);
        // Map URL to controller via patterns
        $action = 'index';
        $name = 'home';
        foreach ($this->routes as $pattern => $route) {
            if (preg_match($pattern, substr($this->request->getUrlPart('path'), 1), $args)) {
                if (strpos($route, '$') === 0) {
                    if ($args[substr($route, 1)]) {
                        $name = $args[substr($route, 1)];
                    }
                } else {
                    $name = $route;
                }
                if (isset($args['ACTION']) && $args['ACTION']) {
                    $action = $args['ACTION'];
                }
                break;
            }
        }
        return array($name, $type, $action, $args);
    }
    protected function doesControllerActionExist ($controllerClass, $action) {
        return class_exists($controllerClass) && method_exists($controllerClass, $action);
    }
    protected function getControllerSupport ($controllerRoot, $action, $requestMethod) {
        $methods = array();
        $mimeTypes = array();
        $classes = Controller::getClasses();
        foreach ($classes as $mimeType => $typeClasses) {
            foreach ($typeClasses as $type => $method) {
                $controllerClass = $controllerRoot . $type;
                if ($this->doesControllerActionExist($controllerClass, $action)) {
                    $methods[] = $method;
                    if ($requestMethod === $method) {
                        $mimeTypes[] = $mimeType;
                    }
                }
            }
        }
        return array(
            'methods' => array_unique($methods),
            'mimeTypes' => array_unique($mimeTypes),
        );
    }
    /**
     * Get a controller to deal with a request
     *
     * @return Core\Controller\Base
     * @throws Core\HttpStatus
    **/
    protected function getController () {
        // Load the controllers for this route
        list($name, $type, $action, $args) = $this->mapRequestToControllerParts();
        $controllerFile = CONTROLLER_DIR . "/{$name}.controller.php";
        if (!file_exists($controllerFile)) {
            // Check whether there's a simple page template
            $pageController = new Controller\Page($this->request, $name, $action, $args);
            if ($pageController->exists()) {
                return $pageController;
            }
            
            throw new HttpStatus\NotFound();
        }
        require_once($controllerFile);
        
        // Choose a controller class to deal with this request
        $controllerRoot = "App\\Controller\\{$name}";
        $controllerClass = $controllerRoot . $type;
        $sharedClass = "{$controllerRoot}Shared";
        
        if (!$this->doesControllerActionExist($controllerClass, $action)) {
            
            // There's no valid action for this request. Check if the URL is available for other request types
            $requestMethod = $this->request->getMethod();
            $support = $this->getControllerSupport($controllerRoot, $action, $requestMethod);
            
            // a) URL valid in another mime/type
            if (!empty($support['mimeTypes'])) {
                $exception = new HttpStatus\NotAcceptable("Not all mime/types are accepted for $requestMethod requests at this URL. Accepted mime/types are: " . implode(", ", $support['mimeTypes']));
                $exception->setAcceptedMimeTypes($support['mimeTypes']);
                throw $exception;
            }
            
            // b) URL valid with another method
            if (!empty($support['methods'])) {
                $exception = new HttpStatus\MethodNotAllowed("$requestMethod requests aren’t allowed at this URL. Allowed methods are: " . implode(", ", $support['methods']));
                $exception->setAllowedMethods($support['methods']);
                throw $exception;
            }
            
            // c) Shared class exists
            if (class_exists($sharedClass)) {
                $shared = new $sharedClass($this->request, $name, $action, $args);
                if ($shared->hasAction()) {
                    $shared->setUp();
                    return $shared;
                }
            }
            
            // d) 404d
            throw new HttpStatus\NotFound();
        }
        
        $controller = new $controllerClass($this->request, $name, $action, $args);
        // Run the shared controller action
        if (class_exists($sharedClass)) {
            $shared = new $sharedClass($this->request, $name, $action, $args, $controller);
            $shared->setUp();
            $shared->action();
        }
        
        return $controller;
    }
    
    public function reportError ($error, $log = true) {
        if ($log) {
            error_log($error);
        }
        if (defined('MAIL_ERRORS') && MAIL_ERRORS) {
            Email::sendRaw(SITE_EMAIL, '[' . SITE_NAME . '] Error', "$error\n\n" . Dump::light($this->request, true));
        }
    }
    
    public function shutdownFunction () {
        if ($lastError = error_get_last()) {
            $eType = $lastError['type'];
            if (($eType & (E_ERROR | E_PARSE | E_COMPILE_ERROR | E_USER_ERROR)) == $eType) {
                $eMap = array(
                    E_ERROR => 'Fatal',
                    E_PARSE => 'Parse',
                    E_COMPILE_ERROR => 'Compiler',
                    E_USER_ERROR => 'User fatal',
                );
                $error = new Exception("{$eMap[$eType]} error: {$lastError['message']} in {$lastError['file']} on line {$lastError['line']}", $eType);
                $this->reportError($error->getMessage(), false);
                $exception = new HttpStatus\InternalServerError("An unexpected error occured", $error);
                $response = $this->generateExceptionResponse($exception);
                $response->respond();
            }
        }
    }
    /**
     * Process the request by mapping the URL to a controller class and method and render a response
    **/
    public function processRequest () {
        register_shutdown_function(array($this, 'shutdownFunction'));
        
        try {
            // Map the request to a controller action and generate its response
            $controller = $this->getController();
            $response = $controller->generateResponse();
        } catch (Exception $exception) {
            if (!$exception instanceof HttpStatus\Base) {
                $this->reportError((string) $exception);
                $exception = new HttpStatus\InternalServerError($exception->getMessage(), $exception);
            }
            $response = $this->generateExceptionResponse($exception);
        }
        return $response;
    }
}
