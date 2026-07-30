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

    // ──────────────────────────────────────────────
    // Bootstrapping
    // ──────────────────────────────────────────────

    public function __construct() {
        $this->bootAttributes();
    }

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

    // ──────────────────────────────────────────────
    // Schema Access
    // ──────────────────────────────────────────────

    /**
     * Mendapatkan Schema instance (lazy-loaded dari Manager).
     */
    protected function getDb(): Schema {
        if (!isset($this->db)) {
            $this->db = Manager::get($this->schemaName);
        }
        return $this->db;
    }

    /**
     * Membuat Schema instance baru (fresh query builder state).
     */
    protected function newDb(): Schema {
        return Manager::get($this->schemaName);
    }

    // ──────────────────────────────────────────────
    // Query Entry Point
    // ──────────────────────────────────────────────

    /**
     * Entry point untuk memulai query builder chain.
     * Mengembalikan instance baru dengan Schema fresh.
     */
    public static function query(): static {
        $instance = new static();
        $instance->db = $instance->newDb();
        return $instance;
    }

    // ──────────────────────────────────────────────
    // Find / Read
    // ──────────────────────────────────────────────

    /**
     * Mencari record berdasarkan primary key.
     */
    public static function find(int|string $id): ?static {
        $instance = new static();
        $db = $instance->newDb();

        $result = $db->where([[$instance->primaryKey, $id]])
            ->limit(1)
            ->get($instance->table);

        $row = $result->row();
        if ($row === null) {
            return null;
        }

        return $instance->hydrate($row);
    }

    /**
     * Mengambil semua record dari tabel.
     *
     * @return static[]
     */
    public static function all(): array {
        $instance = new static();
        $db = $instance->newDb();

        $result = $db->get($instance->table);
        $rows = $result->result();

        if ($rows === null || $rows === false) {
            return [];
        }

        return array_map(
            fn(\stdClass $row): static => (new static())->hydrate($row),
            $rows
        );
    }

    // ──────────────────────────────────────────────
    // Create
    // ──────────────────────────────────────────────

    /**
     * Insert record baru dan mengembalikan instance model.
     */
    public static function create(array $data): static {
        $instance = new static();
        $db = $instance->newDb();

        // Konversi key array ke column name yang sesuai
        $insertData = $instance->mapToColumns($data);

        if ($db->insert($instance->table, $insertData)) {
            $id = $db->lastId();
            if ($id > 0) {
                // Set primary key dari lastInsertId
                $propName = $instance->propertyMap[$instance->primaryKey] ?? null;
                if ($propName !== null) {
                    $instance->$propName = $id;
                }
            }
            // Set attributes dari data yang diinsert
            foreach ($data as $key => $value) {
                $instance->setAttribute($key, $value);
            }
            $instance->exists = true;
            $instance->syncOriginal();
        }

        return $instance;
    }

    // ──────────────────────────────────────────────
    // Save (Insert or Update)
    // ──────────────────────────────────────────────

    /**
     * Menyimpan record. Insert jika baru, update jika sudah ada.
     */
    public function save(): bool {
        if ($this->exists) {
            return $this->performUpdate();
        }
        return $this->performInsert();
    }

    /**
     * Update record saat ini berdasarkan primary key.
     */
    public function update(array $data): bool {
        // Set nilai baru ke properties
        foreach ($data as $key => $value) {
            $this->setAttribute($key, $value);
        }
        return $this->performUpdate();
    }

    /**
     * Menghapus record saat ini dari database.
     */
    public function delete(): bool {
        if (!$this->exists) {
            return false;
        }

        $pkValue = $this->getPrimaryKeyValue();
        if ($pkValue === null) {
            return false;
        }

        $result = $this->getDb()
            ->where([[$this->primaryKey, $pkValue]])
            ->delete($this->table);

        if ($result) {
            $this->exists = false;
        }

        return (bool) $result;
    }

    // ──────────────────────────────────────────────
    // Internal: Insert / Update helpers
    // ──────────────────────────────────────────────

    protected function performInsert(): bool {
        $data = $this->getColumnData();
        $result = $this->getDb()->insert($this->table, $data);

        if ($result) {
            $id = $this->getDb()->lastId();
            if ($id > 0) {
                $propName = $this->propertyMap[$this->primaryKey] ?? null;
                if ($propName !== null) {
                    $this->$propName = $id;
                }
            }
            $this->exists = true;
            $this->syncOriginal();
            return true;
        }

        return false;
    }

    protected function performUpdate(): bool {
        $pkValue = $this->getPrimaryKeyValue();
        if ($pkValue === null) {
            return false;
        }

        $data = $this->getColumnData();
        // Jangan update primary key
        unset($data[$this->primaryKey]);

        if (empty($data)) {
            return true; // tidak ada yang perlu diupdate
        }

        $result = $this->getDb()
            ->where([[$this->primaryKey, $pkValue]])
            ->update($this->table, $data);

        if ($result) {
            $this->syncOriginal();
            return true;
        }

        return false;
    }

    // ──────────────────────────────────────────────
    // Hydration
    // ──────────────────────────────────────────────

    /**
     * Mengisi model instance dari data row database (stdClass).
     */
    public function hydrate(\stdClass $row): static {
        foreach ($row as $colName => $value) {
            $propName = $this->propertyMap[$colName] ?? null;
            if ($propName !== null) {
                $this->$propName = $value;
            }
        }

        $this->exists = true;
        $this->syncOriginal();
        return $this;
    }

    // ──────────────────────────────────────────────
    // Column ↔ Property Mapping
    // ──────────────────────────────────────────────

    /**
     * Mengkonversi data dengan key nama kolom/properti ke nama kolom database.
     * Input bisa menggunakan property name atau column name sebagai key.
     */
    protected function mapToColumns(array $data): array {
        $result = [];
        foreach ($data as $key => $value) {
            // Jika key adalah column name yang valid, gunakan langsung
            if (isset($this->propertyMap[$key])) {
                $result[$key] = $value;
            }
            // Jika key adalah property name, konversi ke column name
            elseif (isset($this->columnMap[$key])) {
                $result[$this->columnMap[$key]] = $value;
            }
        }
        return $result;
    }

    /**
     * Mendapatkan data kolom dari nilai property saat ini.
     */
    protected function getColumnData(): array {
        $data = [];
        foreach ($this->columnMap as $propName => $colName) {
            $data[$colName] = $this->$propName;
        }
        return $data;
    }

    /**
     * Mendapatkan nilai primary key dari instance saat ini.
     */
    protected function getPrimaryKeyValue(): int|string|null {
        $propName = $this->propertyMap[$this->primaryKey] ?? null;
        if ($propName === null) return null;
        return $this->$propName;
    }

    // ──────────────────────────────────────────────
    // Attribute Management
    // ──────────────────────────────────────────────

    /**
     * Set nilai attribute (property name atau column name).
     */
    protected function setAttribute(string $key, mixed $value): void {
        // Jika key adalah property name
        if (isset($this->columnMap[$key])) {
            $this->$key = $value;
            return;
        }
        // Jika key adalah column name
        $propName = $this->propertyMap[$key] ?? null;
        if ($propName !== null) {
            $this->$propName = $value;
        }
    }

    /**
     * Menyimpan snapshot data saat ini sebagai original.
     */
    protected function syncOriginal(): void {
        $this->original = $this->getColumnData();
    }
}