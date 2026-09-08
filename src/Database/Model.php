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
            return true;
        }
        return false;
    }
}