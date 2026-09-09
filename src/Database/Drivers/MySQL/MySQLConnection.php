<?php 

namespace Selvi\Database\Drivers\MySQL;

use Override;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
use Selvi\Database\Contracts\ConnectionInterface;
use Selvi\Database\Contracts\GrammarInterface;

class MySQLConnection implements ConnectionInterface {

    private array $config;
    private ?PDO $pdo = null;
    private ?GrammarInterface $grammar = null;

    public function __construct(array $config) {
        $this->config = $config;
    }

    public function getConfig(): array {
        return $this->config;
    }

    #[Override]
    public function getGrammar(): GrammarInterface
    {
        if ($this->grammar === null) {
            throw new RuntimeException('Grammar belum diset pada connection. Driver harus memanggil setGrammar().');
        }
        return $this->grammar;
    }

    public function setGrammar(GrammarInterface $grammar): void
    {
        $this->grammar = $grammar;
    }

    public function pdo(): PDO {
        $this->ensureConnected();
        return $this->pdo;
    }

    #[Override]
    public function connect(): bool
    {
        if ($this->pdo instanceof PDO) return true;

        $dsn = 'mysql:host='.($this->config['host'] ?? 'localhost');
        if (!empty($this->config['port'])) $dsn .= ';port='.$this->config['port'];
        if (!empty($this->config['database'])) $dsn .= ';dbname='.$this->config['database'];
        if (!empty($this->config['charset'])) $dsn .= ';charset='.$this->config['charset'];
        if (!empty($this->config['socket'])) $dsn .= ';unix_socket='.$this->config['socket'];

        $options = array_replace([
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ], (array) ($this->config['options'] ?? []));

        try {
            $this->pdo = new PDO(
                $dsn,
                $this->config['username'] ?? null,
                $this->config['password'] ?? null,
                $options
            );
        } catch (PDOException $e) {
            $this->pdo = null;
            throw new RuntimeException('Gagal terhubung ke MySQL: '.$e->getMessage(), (int) $e->getCode());
        }

        return true;
    }

    #[Override]
    public function disconnect(): bool
    {
        $this->pdo = null;
        return true;
    }

    public function isConnected(): bool
    {
        return $this->pdo instanceof PDO;
    }

    public function beginTransaction(): bool
    {
        return $this->pdo()->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo()->commit();
    }

    public function rollback(): bool
    {
        return $this->pdo()->rollBack();
    }

    /**
     * Eksekusi query SELECT, mengembalikan semua baris (array of assoc).
     */
    public function select(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll() ?: [];
    }

    /**
     * Eksekusi query INSERT, mengembalikan lastInsertId.
     */
    public function insert(string $sql, array $params = []): int|string
    {
        $this->run($sql, $params);
        return $this->pdo()->lastInsertId();
    }

    /**
     * Eksekusi query UPDATE, mengembalikan jumlah baris terpengaruh.
     */
    public function update(string $sql, array $params = []): int
    {
        return $this->run($sql, $params)->rowCount();
    }

    /**
     * Eksekusi query DELETE, mengembalikan jumlah baris terpengaruh.
     */
    public function delete(string $sql, array $params = []): int
    {
        return $this->run($sql, $params)->rowCount();
    }

    private function run(string $sql, array $params = []): PDOStatement
    {
        $this->ensureConnected();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    private function ensureConnected(): void
    {
        if (!$this->pdo instanceof PDO) {
            $this->connect();
        }
    }

}