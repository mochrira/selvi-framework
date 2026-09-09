<?php 

namespace Selvi\Database;

use InvalidArgumentException;
use Selvi\Database\Contracts\ConnectionInterface;
use Selvi\Database\Contracts\GrammarInterface;

class QueryBuilder {

    private const OPERATORS = ['=', '!=', '<>', '>', '<', '>=', '<=', 'LIKE'];

    private ConnectionInterface $connection;
    private ?string $table = null;
    private string|array $columns = '*';
    /** @var array<int, array{0: string, 1: string, 2: mixed}> */
    private array $wheres = [];
    private ?int $limit = null;
    private ?int $offset = null;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    public function table(string $table): static
    {
        $this->table = $table;
        return $this;
    }

    public function select(string|array $columns = '*'): static
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Kondisi WHERE (di-AND-kan). Contoh:
     *   where('id', 5)             // id = 5
     *   where('harga', '>=', 1000)
     *   where('nama', 'LIKE', '%kopi%')
     */
    public function where(string $column, mixed $operator, mixed $value = null): static
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $operator = strtoupper((string) $operator);
        if (!in_array($operator, self::OPERATORS, true)) {
            throw new InvalidArgumentException("Operator WHERE '{$operator}' untuk kolom '{$column}' tidak didukung.");
        }

        $this->wheres[] = [$column, $operator, $value];
        return $this;
    }

    public function limit(?int $limit): static
    {
        // Nilai negatif (misal -1) atau null dianggap tanpa limit
        $this->limit = ($limit !== null && $limit < 0) ? null : $limit;
        return $this;
    }

    public function offset(?int $offset): static
    {
        if ($offset !== null && $offset < 0) {
            throw new InvalidArgumentException("Nilai offset tidak boleh negatif: {$offset}");
        }
        $this->offset = $offset;
        return $this;
    }

    /**
     * Eksekusi SELECT, mengembalikan semua baris (array of assoc).
     */
    public function get(): array
    {
        [$sql, $params] = $this->grammar()->compileSelect($this);
        return $this->connection->select($sql, $params);
    }

    /**
     * INSERT, mengembalikan lastInsertId.
     */
    public function insert(array $data): int|string
    {
        if ($data === []) throw new InvalidArgumentException('Data untuk INSERT tidak boleh kosong.');

        [$sql, $params] = $this->grammar()->compileInsert($this, $data);
        return $this->connection->insert($sql, $params);
    }

    /**
     * UPDATE (memakai kondisi WHERE dari where()), mengembalikan jumlah baris terpengaruh.
     */
    public function update(array $data): int
    {
        if ($data === []) throw new InvalidArgumentException('Data untuk UPDATE tidak boleh kosong.');

        [$sql, $params] = $this->grammar()->compileUpdate($this, $data);
        return $this->connection->update($sql, $params);
    }

    /**
     * DELETE (memakai kondisi WHERE dari where()), mengembalikan jumlah baris terpengaruh.
     */
    public function delete(): int
    {
        [$sql, $params] = $this->grammar()->compileDelete($this);
        return $this->connection->delete($sql, $params);
    }

    // ──────────────────────────────────────────────
    // Akses state (dipakai Grammar)
    // ──────────────────────────────────────────────

    public function getTable(): ?string
    {
        return $this->table;
    }

    public function getColumns(): string|array
    {
        return $this->columns;
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: mixed}>
     */
    public function getWheres(): array
    {
        return $this->wheres;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getOffset(): ?int
    {
        return $this->offset;
    }

    private function grammar(): GrammarInterface
    {
        return $this->connection->getGrammar();
    }

}
