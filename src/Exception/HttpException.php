<?php 

declare(strict_types=1);

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

    public function getUri(): ?string {
        return $this->uri;
    }

    public function getMethod(): ?string {
        return $this->method;
    }

}