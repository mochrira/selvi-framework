<?php

declare(strict_types=1);

namespace Selvi\Database\Drivers\PostgreSQL;

use Override;
use PgSql\Connection;
use Selvi\Database\Interfaces\IConnection;
use Selvi\Database\Interfaces\IGrammar;
use Selvi\Database\Interfaces\IResult;
use Selvi\Exception\DatabaseException;

/**
 * Koneksi PostgreSQL berbasis ekstensi `pgsql`.
 *
 * Konfigurasi memakai kunci yang sama dengan driver MySQL (host, port,
 * username, password, database). `user` dan `dbname` juga diterima sebagai
 * alias. `sslmode`, `connect_timeout`, dan `application_name` diteruskan apa
 * adanya ke libpq bila diisi.
 */
class PostgreSQLConnection implements IConnection {

    private array $config;
    private Connection $instance;

    public function __construct(array $config) {
        $this->config = $config;
        $this->connect();
    }

    #[Override]
    public function getConfig(): array | null {
        return $this->config;
    }

    #[Override]
    public function connect(): bool {
        if(isset($this->instance)) return true;

        // pg_connect melaporkan kegagalan lewat warning + false, bukan exception.
        $instance = @pg_connect($this->dsn());

        if($instance === false) {
            $detail = error_get_last()['message'] ?? '';
            throw new DatabaseException(
                str_contains($detail, 'pg_connect')
                    ? $detail
                    : 'Koneksi PostgreSQL gagal. Periksa host, port, dan kredensial.'
            );
        }

        $this->instance = $instance;
        return true;
    }

    #[Override]
    public function disconnect(): bool {
        if(!isset($this->instance)) return false;

        $closed = pg_close($this->instance);
        unset($this->instance);

        return $closed;
    }

    #[Override]
    public function grammar(): IGrammar {
        return new PostgreSQLGrammar(new PostgreSQLSanitizer());
    }

    #[Override]
    public function query(string $sql): IResult | bool {
        if(!isset($this->instance)) $this->connect();

        // pg_send_query + pg_get_result dipakai supaya SQLSTATE tetap terbaca
        // saat query gagal; pg_query() hanya mengembalikan false.
        if(@pg_send_query($this->instance, $sql) === false) {
            throw new DatabaseException(pg_last_error($this->instance), 500, null, $sql);
        }

        $result = pg_get_result($this->instance);

        if($result === false) {
            throw new DatabaseException(pg_last_error($this->instance), 500, null, $sql);
        }

        $error = pg_result_error($result);

        if($error !== '' && $error !== false) {
            $state = pg_result_error_field($result, PGSQL_DIAG_SQLSTATE);
            throw new DatabaseException($error, 500, is_string($state) ? $state : null, $sql);
        }

        if(pg_result_status($result) === PGSQL_TUPLES_OK) {
            return new PostgreSQLResult($result);
        }

        return true;
    }

    /**
     * Nilai sequence terakhir pada sesi ini.
     *
     * LASTVAL() bukan per-statement seperti LAST_INSERT_ID() di MySQL; batasan
     * ini didokumentasikan di docs/04-database/01-koneksi.md. Bila belum ada
     * sequence yang dipakai di sesi ini, PostgreSQL melempar error dan driver
     * mengembalikan 0 supaya pemanggil tidak menerima exception dari insert
     * yang sebenarnya berhasil.
     */
    #[Override]
    public function lastId(): int {
        try {
            $result = $this->query('SELECT LASTVAL() AS lastid');
        } catch(DatabaseException) {
            return 0;
        }

        if(!$result instanceof IResult) return 0;

        $row = $result->row();
        return is_object($row) ? (int) $row->lastid : 0;
    }

    #[Override]
    public function createDatabase(string $name): bool {
        $sanitizer = new PostgreSQLSanitizer();

        $exists = $this->query(
            'SELECT 1 FROM pg_database WHERE datname = ' . $sanitizer->sanitize($name)
        );

        if($exists instanceof IResult && $exists->num_rows() > 0) return true;

        $identifier = '"' . str_replace('"', '""', $name) . '"';

        return $this->query("CREATE DATABASE {$identifier}");
    }

    #[Override]
    public function startTransaction(): bool {
        return $this->query('BEGIN');
    }

    #[Override]
    public function commit(): bool {
        return $this->query('COMMIT');
    }

    #[Override]
    public function rollback(): bool {
        return $this->query('ROLLBACK');
    }

    /**
     * Menyusun connection string libpq dari config.
     *
     * Nilai di-quote dengan tanda kutip tunggal dan backslash di-escape, sesuai
     * aturan conninfo libpq.
     */
    private function dsn(): string {
        $mapping = [
            'host' => 'host',
            'port' => 'port',
            'database' => 'dbname',
            'username' => 'user',
            'password' => 'password',
        ];

        $parts = [];

        foreach($mapping as $configKey => $dsnKey) {
            $value = $this->config[$configKey] ?? null;

            if($value === null && $configKey === 'database') {
                $value = $this->config['dbname'] ?? null;
            }
            if($value === null && $configKey === 'username') {
                $value = $this->config['user'] ?? null;
            }
            if($value === null || $value === '') continue;

            $parts[] = $dsnKey . '=' . $this->quoteDsnValue((string) $value);
        }

        foreach(['sslmode', 'connect_timeout', 'application_name'] as $key) {
            if(!isset($this->config[$key]) || $this->config[$key] === '') continue;
            $parts[] = $key . '=' . $this->quoteDsnValue((string) $this->config[$key]);
        }

        return implode(' ', $parts);
    }

    private function quoteDsnValue(string $value): string {
        return "'" . str_replace(['\\', "'"], ['\\\\', "\\'"], $value) . "'";
    }

}
