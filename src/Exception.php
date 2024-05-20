<?php

namespace Selvi;
use Exception as PHPException;

class Exception extends PHPException {

    private $codeString;
    private $additionalData;

    public function __construct($message, $codeString, $error = 500, $additionalData = null) {
        parent::__construct($message, $error);
        $this->codeString = $codeString;
        $this->additionalData = $additionalData;
    }

    public function getCodeString() {
        return $this->codeString ?? null;
    }

    public function getAdditionalData() {
        return $this->additionalData ?? null;
    }

}