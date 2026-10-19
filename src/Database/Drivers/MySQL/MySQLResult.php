<?php 

declare(strict_types=1);

namespace Selvi\Database\Drivers\MySQL;

use \mysqli_result;
use Selvi\Database\Interfaces\IResult;
use stdClass;

class MySQLResult implements IResult {

    private mixed $result;

    public function __construct(mixed $result) {
        $this->result = $result;
    }

    public function num_rows(): int | bool {
        if(is_bool($this->result)) return $this->result;
        return $this->result->num_rows;
    }

    public function rows(): array | bool | null {
        if(is_bool($this->result)) return $this->result;
        if($this->result instanceof mysqli_result) {
            $this->result->data_seek(0);
            $res = [];
            while($row = $this->result->fetch_object()) {
                $res[] = $row;
            }
            return $res;
        }
        return null;
    }

    /**
     * @deprecated since 3.3.1, use rows() instead.
     */
    public function result(): array | bool | null {
        return $this->rows();
    }

    public function row(): stdClass | bool | null {
        if(is_bool($this->result)) return $this->result;
        if($this->result instanceof mysqli_result) {
            $this->result->data_seek(0);
            return $this->result->fetch_object();
        }
        return null;
    }

}