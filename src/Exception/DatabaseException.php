<?php 

namespace Selvi\Exception;

use mysqli_sql_exception;
use Selvi\Exception;

class DatabaseException extends Exception {

    private ?string $sql = null;

    function __construct(mysqli_sql_exception $e, string $sql = null) {
        parent::__construct('(SQL State : '.$e->getSqlState().') '.$e->getMessage(), 'database/error', 500);
        $this->sql = $sql;
    }

    function getSql() {
        return $this->sql;
    }

}