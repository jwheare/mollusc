<?php

namespace Core\Response;
use Core\Request;
use Core\HttpStatus;

class JsonError extends Json {
    public function __construct (Request $request, HttpStatus\BaseError $exception) {
        parent::__construct($request);
        $this->setException($exception);
        $this->error = true;
        $this->code = $this->exception->getCode();
        $this->text = $this->exception->getText();
        $this->message = $this->exception->getMessage();
        $this->exception->processResponse($this);
    }
}
