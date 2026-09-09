<?php 

namespace Selvi\Database;

use Selvi\Database\Attributes\Column;
use Selvi\Database\Attributes\Primary;
use Selvi\Database\Attributes\Schema as SchemaAttr;
use Selvi\Database\Attributes\Table;

abstract class Model {

    protected string $schemaName;
    protected string $table;
    protected string $primaryKey;
    /** @var array<string, mixed> Map: propertyName => columnName */
    protected array $columnMap = [];
    /** @var array<string, string> Map: columnName => propertyName */
    protected array $propertyMap = [];
    /** @var bool Apakah record ini sudah ada di database */
    protected bool $exists = false;

    function __construct() {
        $this->bootAttributes();
    }

    // ──────────────────────────────────────────────
    // Bootstrapping
    // ──────────────────────────────────────────────

    /**
     * Membaca PHP 8 attributes dari class untuk auto-discovery
     * schema, table, column mapping, dan primary key.
     */
    protected function bootAttributes(): void {
        $reflection = new \ReflectionClass($this);

        // #[Schema("main")]
        $schemaAttrs = $reflection->getAttributes(SchemaAttr::class);
        if (!empty($schemaAttrs)) {
            $this->schemaName = $schemaAttrs[0]->newInstance()->name;
        }

        // #[Table("kontak")]
        $tableAttrs = $reflection->getAttributes(Table::class);
        if (!empty($tableAttrs)) {
            $this->table = $tableAttrs[0]->newInstance()->name;
        }

        // Scan properties untuk #[Column], #[Primary]
        foreach ($reflection->getProperties() as $property) {
            $columnAttrs = $property->getAttributes(Column::class);
            if (empty($columnAttrs)) continue;

            $colName = $columnAttrs[0]->newInstance()->name;
            $propName = $property->getName();

            $this->columnMap[$propName] = $colName;
            $this->propertyMap[$colName] = $propName;

            // #[Primary]
            $primaryAttrs = $property->getAttributes(Primary::class);
            if (!empty($primaryAttrs)) {
                $this->primaryKey = $colName;
            }
        }
    }

    /**
     * Ambil query builder dari koneksi berdasarkan schemaName.
     * Model tidak perlu tahu driver database apa yang digunakan.
     */
    protected function queryBuilder(): QueryBuilder {
        return new QueryBuilder(DatabaseManager::get($this->schemaName));
    }

    // ──────────────────────────────────────────────
    // Query
    // ──────────────────────────────────────────────

    public static function all(?callable $callback = null): array {
        $instance = new static();
        $query = $instance->queryBuilder()->table($instance->table);

        if ($callback !== null) {
            $query = $callback($query) ?? $query;
        }

        $rows = $query->get();

        $records = [];
        foreach ($rows as $row) {
            $item = new static();
            $item->hydrate($row);
            $item->exists = true;
            $records[] = $item;
        }

        return $records;
    }

    public static function find(mixed $id): ?static {
        $instance = new static();
        $rows = $instance->queryBuilder()
            ->table($instance->table)
            ->where($instance->primaryKey, $id)
            ->get();

        $row = $rows[0] ?? null;
        if ($row === null) return null;

        $instance->hydrate($row);
        $instance->exists = true;
        return $instance;
    }

    function create(): bool {
        // Generate data otomatis dari kombinasi columnMap (property => column)
        $data = [];
        foreach ($this->columnMap as $propName => $colName) {
            if ($colName === $this->primaryKey) continue;
            $data[$colName] = $this->$propName ?? null;
        }

        $id = $this->queryBuilder()->table($this->table)->insert($data);

        if ((int) $id > 0) {
            // Konversi nama kolom primary key ke nama property
            $propName = $this->propertyMap[$this->primaryKey] ?? null;
            if ($propName !== null) {
                $this->$propName = (int) $id;
            }
            $this->exists = true;
            return true;
        }
        return false;
    }

    public function update(?array $data = null): bool {
        $primaryProperty = $this->propertyMap[$this->primaryKey] ?? null;

        if ($primaryProperty === null || !isset($this->$primaryProperty)) return false;

        $primaryValue = $this->$primaryProperty;

        if ($data !== null) {
            foreach ($data as $key => $value) {
                if (isset($this->columnMap[$key])) {
                    $this->$key = $value;
                } elseif (isset($this->propertyMap[$key])) {
                    $prop = $this->propertyMap[$key];
                    $this->$prop = $value;
                }
            }
        }

        $updateData = [];

        foreach ($this->columnMap as $propName => $colName) {
            if ($colName === $this->primaryKey) continue;
            $updateData[$colName] = $this->$propName ?? null;
        }

        return (bool) $this->queryBuilder()
            ->table($this->table)
            ->where($this->primaryKey, $primaryValue)
            ->update($updateData);
    }

    public function delete(): bool {
        $primaryProperty = $this->propertyMap[$this->primaryKey] ?? null;
        if ($primaryProperty === null || !isset($this->$primaryProperty)) {
            return false;
        }
        $primaryValue = $this->$primaryProperty;

        $result = (bool) $this->queryBuilder()
            ->table($this->table)
            ->where($this->primaryKey, $primaryValue)
            ->delete();

        if ($result) {
            $this->exists = false;
        }
        return $result;
    }

    /**
     * Isi properti model dari satu baris hasil query (kolom => nilai).
     */
    protected function hydrate(array $row): void {
        foreach ($row as $colName => $value) {
            $propName = $this->propertyMap[$colName] ?? null;
            if ($propName !== null) {
                $this->$propName = $value;
            }
        }
    }

    public function toArray(): array {
        $data = [];
        foreach ($this->columnMap as $propName => $colName) {
            $data[$colName] = $this->$propName ?? null;
        }
        return $data;
    }
}