<?php

namespace Selvi;
use Exception as PHPException;

class Exception extends PHPException {

    private $codeString;
    private $data;

    public function __construct($message, $codeString, $error = 500, $data = null) {
        parent::__construct($message, $error);
        $this->codeString = $codeString;
        $this->data = $data;
    }

    public function getCodeString() {
        return $this->codeString ?? null;
    }

    public function getData() {
        return $this->data ?? null;
    }

    public function with($name, $value) {
        $this->data[$name] = $value;
        return $this;
    }

    public function get($name) {
        return $this->data[$name] ?? null;
    }

}