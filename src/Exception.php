<?php 

namespace Selvi;
use Exception as PHPException;

class Exception extends PHPException {

    private $errorCode;

    public function __construct($message, $errorCode, $error = 500) {
        parent::__construct($message, $error);
        $this->errorCode = $errorCode;
    }

    public function getErrorCode() {
        return $this->errorCode;
    }

}