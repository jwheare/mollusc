<?php

namespace Core\Response;
use Core\Request;
use Core\HttpStatus;

class HtmlError extends Html {
    public function __construct (Request $request, HttpStatus\BaseError $exception) {
        parent::__construct($request);
        $this->setException($exception);
        // Default error template
        $this->setTemplate('error', array(
            'title' => $this->getStatus(),
            'message' => $this->exception->getMessage(),
            'exception' => $this->exception,
        ));
        $this->exception->processResponse($this);
    }
}
