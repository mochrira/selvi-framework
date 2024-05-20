<?php 

namespace Selvi\Exception;

use Selvi\Exception;

class HttpException extends Exception {

    private ?string $uri = null;
    private ?string $method = null;

    function __construct(string $message, int $code, string $method, string $uri) {
        parent::__construct($message, 'http/error', $code);
        $this->method = $method;
        $this->uri = $uri;
    }

    function getUri() {
        return $this->uri;
    }

    function getMethod() {
        return $this->method;
    }

}