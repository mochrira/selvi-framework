<?php 

namespace Selvi;
use Exception as PHPException;

class Exception extends PHPException {

    private $errorCode;
    private $additionalData;

    public function __construct($message, $errorCode, $error = 500, $additionalData = null) {
        parent::__construct($message, $error);
        $this->errorCode = $errorCode;
        $this->additionalData = $additionalData;
    }

    public function getErrorCode() {
        return $this->errorCode;
    }

    public function getAdditionalData() {
        return $this->additionalData;
    }

}