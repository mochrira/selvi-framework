<?php

declare(strict_types=1);

namespace Selvi\Database\Drivers\PostgreSQL;

use Override;
use Selvi\Database\Interfaces\IResult;
use stdClass;

/**
 * Hasil query PostgreSQL.
 *
 * PostgreSQL mengirim semua nilai sebagai string, termasuk boolean sebagai
 * 't'/'f'. Karena itu kolom bertipe boolean dinormalkan menjadi '1'/'0' agar
 * perilakunya sama dengan driver MySQL dan `BoolCast` tetap bekerja
 * ((bool) 'f' bernilai true, jadi nilai mentah tidak boleh diteruskan).
 */
class PostgreSQLResult implements IResult {

    private mixed $result;

    /**
     * Nama kolom boolean pada result ini, di-resolve sekali per result.
     *
     * @var array<string, true>
     */
    private array $booleanColumns = [];

    private bool $typesResolved = false;

    public function __construct(mixed $result) {
        $this->result = $result;
    }

    #[Override]
    public function num_rows(): int | bool {
        if(is_bool($this->result)) return $this->result;
        return pg_num_rows($this->result);
    }

    #[Override]
    public function rows(): array | bool | null {
        if(is_bool($this->result)) return $this->result;

        pg_result_seek($this->result, 0);

        $res = [];
        while(($row = pg_fetch_object($this->result)) !== false) {
            $res[] = $this->normalize($row);
        }

        return $res;
    }

    /**
     * @deprecated since 3.3.1, use rows() instead.
     */
    #[Override]
    public function result(): array | bool | null {
        return $this->rows();
    }

    #[Override]
    public function row(): stdClass | bool | null {
        if(is_bool($this->result)) return $this->result;

        pg_result_seek($this->result, 0);
        $row = pg_fetch_object($this->result);

        return $row === false ? null : $this->normalize($row);
    }

    private function normalize(stdClass $row): stdClass {
        $this->resolveTypes();
        if(empty($this->booleanColumns)) return $row;

        foreach($this->booleanColumns as $field => $_) {
            if(isset($row->{$field})) {
                $row->{$field} = $row->{$field} === 't' ? '1' : '0';
            }
        }

        return $row;
    }

    private function resolveTypes(): void {
        if($this->typesResolved || is_bool($this->result)) return;
        $this->typesResolved = true;

        $count = pg_num_fields($this->result);
        for($i = 0; $i < $count; $i++) {
            if(pg_field_type($this->result, $i) === 'bool') {
                $this->booleanColumns[pg_field_name($this->result, $i)] = true;
            }
        }
    }

}
