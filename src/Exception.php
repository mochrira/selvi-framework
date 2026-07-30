<?php

declare(strict_types=1);

namespace Selvi;
use Exception as PHPException;

class Exception extends PHPException {

    private ?string $codeString;
    private mixed $data;

    public function __construct(string $message, ?string $codeString = null, int $error = 500, mixed $data = null) {
        parent::__construct($message, $error);
        $this->codeString = $codeString;
        $this->data = $data;
    }

    public function getCodeString(): ?string {
        return $this->codeString ?? null;
    }

    public function getData(): mixed {
        return $this->data ?? null;
    }

    public function with(string $name, mixed $value): static {
        $this->data[$name] = $value;
        return $this;
    }

    public function get(string $name): mixed {
        return $this->data[$name] ?? null;
    }

}