<?php

namespace Core\Response;
use Core\Request;

class Html extends Base {
    private $template;
    private $context = array();
    public function __construct (Request $request) {
        parent::__construct($request);
        $this->setHeader('Content-type', 'text/html; charset=utf-8');
    }
    public function __get ($name) {
        if ($this->is_set($name)) {
            return $this->context[$name];
        }
    }
    public function __set ($name, $value) {
        $this->context[$name] = $value;
    }
    public function setContext (array $context) {
        foreach ($context as $key => $value) {
            $this->context[$key] = $value;
        }
    }
    public function setTemplate ($template, array $context = null) {
        $this->template = $template;
        if ($context) {
            $this->setContext($context);
        }
    }
    protected function getTemplateFile ($template) {
        return TEMPLATE_DIR . "/{$template}.tpl.php";
    }
    public function templateExists () {
        return file_exists($this->getTemplateFile($this->template));
    }
    public function is_set ($key) {
        return array_key_exists($key, $this->context);
    }
    public function render ($template, array $context = array()) {
        // Set variable context
        extract($context);
        // Use output buffering to store the file's output
        ob_start();
        include $this->getTemplateFile($template);
        $output = ob_get_clean();
        return $output;
    }
    public function out ($template, array $context = array()) {
        echo $this->render($template, $context);
    }
    public function getBody () {
        return $this->render($this->template, $this->context);
    }
}
