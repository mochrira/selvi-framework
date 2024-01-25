<?php 

namespace Selvi\Database\Drivers\SQLSrv;

use Selvi\Database\Result;
use stdClass;

class SQLSrvResult implements Result {

    private $result;

    function __construct(mixed $result) {
        $this->result = $result;
    }

    function num_rows(): int {
        if(is_bool($this->result)) return $this->result;
        return sqlsrv_num_rows($this->result);
    }

    function result(): array | bool | null {
        if(is_bool($this->result)) return $this->result;
        $res = [];
        while($row = sqlsrv_fetch_object($this->result)) $res[] = $row;
        return $res;
    }

    function row(): stdClass | bool | null {
        if(is_bool($this->result)) return $this->result;
        return sqlsrv_fetch_object($this->result, null, null, SQLSRV_SCROLL_ABSOLUTE, 0);
    }

}