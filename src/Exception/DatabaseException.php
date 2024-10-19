<?php 

namespace Selvi\Exception;

use Selvi\Exception;

class DatabaseException extends Exception {

    private ?string $state = null;
    private ?string $sql = null;

    function __construct(string $message, int $code = 500, string $state = null, string $sql = null) {
        parent::__construct($message, 'database/error', $code);
        $this->state = $state;
        $this->sql = $sql;
    }

    function getSql() {
        return $this->sql;
    }

    function getState() {
        return $this->state;
    }

}