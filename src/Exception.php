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
        return $this->errorCode ?? null;
    }

    public function getAdditionalData() {
        return $this->additionalData ?? null;
    }

    public function send() {
        $errorMessage = $this->getMessage() ?? null;
        $errorCode = $this->getErrorCode();
        $errorData = $this->getAdditionalData();

        $content = [];
        if($errorMessage != null) $content['msg'] = $errorMessage;
        if($errorCode != null) $content['code'] = $errorCode;
        if($errorData != null) $content['data'] = $errorData;

        $output = count($content) > 0 ? json_encode($content) : null;
        response($output, $this->getCode())->send();
    }

}