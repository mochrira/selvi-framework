<?php 

declare(strict_types=1);

namespace Selvi\Database\Drivers\MySQL;

use mysqli;
use mysqli_sql_exception;
use Selvi\Database\Contracts\ConnectionInterface;
use Selvi\Database\Contracts\GrammarInterface;
use Selvi\Database\Contracts\ResultInterface;
use Selvi\Exception\DatabaseException;

/**
 * Koneksi MySQL.
 *
 * Implementasi ConnectionInterface yang hanya menangani primitif koneksi:
 * menyediakan Grammar, mengeksekusi SQL mentah, mengambil last insert id, serta
 * mengelola koneksi dan transaksi.
 *
 * Tidak ada state query-builder di sini. Penyusunan SQL adalah tugas QueryBuilder
 * (struktur) dan MySQLGrammar (render) — lihat docs/database/grammar.md.
 */
class MySQLConnection implements ConnectionInterface {

    private array $config;
    private mysqli $instance;

    public function __construct(array $config) {
        $this->config = $config;
        $this->connect();
    }

    public function grammar() : GrammarInterface {
        return new MySQLGrammar(new MySQLSanitizer());
    }

    public function getConfig() : array | null {
        return $this->config;
    }

    public function connect() : bool {
        try {
            if(!isset($this->instance)) {
                $this->instance = new mysqli(
                    $this->config['host'],
                    $this->config['username'],
                    $this->config['password'],
                    $this->config['database'] ?? null,
                    $this->config['port'] ?? null,
                    $this->config['socket'] ?? null
                );
            }
            return true;
        } catch(mysqli_sql_exception $e) {
            throw new DatabaseException($e->getMessage(), 500, $e->getSqlState());
        }
    }

    public function disconnect() : bool {
        if(isset($this->instance)) {
            return $this->instance->close();
        }
        return false;
    }

    public function query(string $sql) : ResultInterface | bool {
        try {
            $result = $this->instance->query($sql);

            if(is_bool($result)) return $result;

            return new MySQLResult($result);
        } catch(mysqli_sql_exception $e) {
            throw new DatabaseException($e->getMessage(), 500, $e->getSqlState(), $sql);
        }
    }

    public function lastId() : int {
        $result = $this->query("SELECT LAST_INSERT_ID() AS lastid");

        if(!$result instanceof ResultInterface) return 0;

        $row = $result->row();

        return is_object($row) ? (int) $row->lastid : 0;
    }

    public function startTransaction() : bool {
        return $this->query("START TRANSACTION");
    }

    public function commit() : bool {
        return $this->query("COMMIT");
    }

    public function rollback() : bool {
        return $this->query("ROLLBACK");
    }

}
