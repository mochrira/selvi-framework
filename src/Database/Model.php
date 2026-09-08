<?php 

namespace Selvi\Database;

use Selvi\Database\Attributes\Column;
use Selvi\Database\Attributes\Primary;
use Selvi\Database\Attributes\Schema as SchemaAttr;
use Selvi\Database\Attributes\Table;

abstract class Model {

    protected Schema $db;
    protected string $schemaName;
    protected string $table;
    protected string $primaryKey;
    /** @var array<string, mixed> Map: propertyName => columnName */
    protected array $columnMap = [];
    /** @var array<string, string> Map: columnName => propertyName */
    protected array $propertyMap = [];
    /** @var array<string, mixed> Snapshot dari database */
    protected array $original = [];
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

    public static function all(): array {
        $instance = new static();
        $db = Manager::get($instance->schemaName);
        $result = $db->get($instance->table);
        $rows = $result ? $result->result() : null;

        if ($rows === null || $rows === false) {
            return [];
        }

        $records = [];
        foreach ($rows as $row) {
            $item = new static();
            foreach ($row as $colName => $value) {
                $propName = $item->propertyMap[$colName] ?? null;
                if ($propName !== null) {
                    $item->$propName = $value;
                }
            }
            $item->exists = true;
            $records[] = $item;
        }

        return $records;
    }

    public static function find(mixed $id): ?static {
        $instance = new static();
        $db = Manager::get($instance->schemaName);
        $result = $db->where([[$instance->primaryKey, $id]])->get($instance->table);
        $row = $result ? $result->row() : null;

        if (!$row) return null;

        foreach ($row as $colName => $value) {
            $propName = $instance->propertyMap[$colName] ?? null;
            if ($propName !== null) $instance->$propName = $value;
        }

        $instance->exists = true;
        return $instance;
    }

    function create(): bool {
        // Generate data otomatis dari kombinasi columnMap (property => column)
        $data = [];
        foreach ($this->columnMap as $propName => $colName) {
            $data[$colName] = $this->$propName ?? null;
        }

        $db = Manager::get($this->schemaName);
        if($db->insert($this->table, $data)) {
            $id = $db->lastId();
            if ($id > 0) {
                // Konversi nama kolom primary key ke nama property
                $propName = $this->propertyMap[$this->primaryKey] ?? null;
                if ($propName !== null) {
                    $this->$propName = $id;
                }
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
            if ($colName === $this->primaryKey) {
                continue;
            }
            $updateData[$colName] = $this->$propName ?? null;
        }

        $db = Manager::get($this->schemaName);

        $result = $db->where([[$this->primaryKey, $primaryValue]])->update($this->table, $updateData);
        return (bool) $result;
    }

    public function delete(): bool {
        $primaryProperty = $this->propertyMap[$this->primaryKey] ?? null;
        if ($primaryProperty === null || !isset($this->$primaryProperty)) {
            return false;
        }
        $primaryValue = $this->$primaryProperty;

        $db = Manager::get($this->schemaName);
        $result = $db->where([[$this->primaryKey, $primaryValue]])->delete($this->table);
        if ($result) {
            $this->exists = false;
        }
        return (bool) $result;
    }

    public function toArray(): array {
        $data = [];
        foreach ($this->columnMap as $propName => $colName) {
            $data[$colName] = $this->$propName ?? null;
        }
        return $data;
    }
}