<?php

namespace Selvi\Database\Builder\DDL;

use InvalidArgumentException;
use Selvi\Database\Builder\DDL\Clauses\ColumnClause;
use Selvi\Database\Contracts\AlterBlueprintInterface;

/**
 * Collector operasi untuk alter().
 *
 * Bentuk pemakaiannya sengaja dibuat sama dengan create():
 *
 *     $schema->alter('kontak', function (AlterBlueprint $table) {
 *         $table->integer('umur')->nullable();                     // ADD COLUMN
 *         $table->string('nmKontak', 200)->nullable()->change();   // MODIFY COLUMN
 *         $table->renameColumn('nmKontak', 'nama');                // RENAME COLUMN
 *         $table->dropColumn('idGrup');                            // DROP COLUMN
 *         $table->index(['nama'], 'idx_nama');                      // ADD INDEX
 *         $table->unique(['nama'], 'uniq_nama');                    // ADD UNIQUE INDEX
 *         $table->dropIndex('idx_lama');                            // DROP INDEX
 *     });
 *
 * Operasi dicatat dalam urutan pemanggilan dan itulah urutan yang dilihat
 * Grammar. Operasi yang tidak didukung driver tertentu diabaikan oleh Grammar
 * driver tersebut, bukan ditolak di sini.
 *
 * Objek ini hanya menyimpan struktur; rendering SQL adalah tugas Grammar dan
 * eksekusinya tugas SchemaBuilder.
 */
class AlterBlueprint implements AlterBlueprintInterface {

    use HasColumnTypes;

    /**
     * Operasi yang bukan definisi kolom. Operasi kolom (add/modify) memakai
     * konstanta milik ColumnClause, karena penandanya melekat pada kolomnya.
     */
    public const OPERATION_DROP = 'drop';
    public const OPERATION_RENAME = 'rename';
    public const OPERATION_INDEX = 'index';
    public const OPERATION_DROP_INDEX = 'dropIndex';
    public const OPERATION_PRIMARY = 'primary';
    public const OPERATION_DROP_PRIMARY = 'dropPrimary';
    public const OPERATION_FOREIGN = 'foreign';
    public const OPERATION_DROP_FOREIGN = 'dropForeign';

    /**
     * Aksi referensial yang dikenal. Divalidasi di builder supaya salah ketik
     * tertangkap sebelum sampai ke database. MySQL/InnoDB menolak SET DEFAULT —
     * itu batasan driver, jadi tetap disertakan di daftar ini.
     */
    private const FOREIGN_ACTIONS = ['CASCADE', 'SET NULL', 'RESTRICT', 'NO ACTION', 'SET DEFAULT'];

    /**
     * Catatan mentah operasi, sesuai urutan pemanggilan.
     *
     * Node operasi kolom menyimpan OBJEK ColumnClause (bukan array), supaya
     * change() yang dipanggil setelahnya tetap terbaca saat getOperations().
     *
     * @var array<int, array<string, mixed>>
     */
    private array $operations = [];

    public function __construct(private readonly string $table) { }

    /**
     * Menghapus satu kolom.
     */
    public function dropColumn(string $name): static {
        $this->operations[] = [
            'operation' => self::OPERATION_DROP,
            'name' => trim($name),
        ];

        return $this;
    }

    /**
     * Mengganti nama kolom, TANPA menyentuh tipe atau atribut lainnya.
     *
     * Dipisahkan dari change() dengan sengaja: pada MySQL 5.7 keduanya dulu
     * digabung dalam CHANGE COLUMN sehingga mengganti nama memaksa penulisan
     * ulang tipe kolom. RENAME COLUMN (MySQL 8+) dan padanan di driver lain tidak
     * menuntut itu.
     */
    public function renameColumn(string $from, string $to): static {
        $this->operations[] = [
            'operation' => self::OPERATION_RENAME,
            'from' => trim($from),
            'to' => trim($to),
        ];

        return $this;
    }

    /**
     * Menambahkan index biasa.
     *
     * Nama index WAJIB disebut. Auto-naming sengaja tidak dilakukan: nama hasil
     * generate berbeda-beda antar driver, padahal nama itulah yang nanti harus
     * disebutkan lagi di dropIndex() — jadi lebih baik ditentukan pemakainya.
     *
     * @param string[] $columns
     */
    public function index(array $columns, string $name): static {
        return $this->addIndex($columns, $name, false);
    }

    /**
     * Menambahkan index unik.
     *
     * @param string[] $columns
     */
    public function unique(array $columns, string $name): static {
        return $this->addIndex($columns, $name, true);
    }

    /**
     * Menghapus index berdasarkan namanya.
     */
    public function dropIndex(string $name): static {
        $this->operations[] = [
            'operation' => self::OPERATION_DROP_INDEX,
            'name' => $this->requireName($name, 'index'),
        ];

        return $this;
    }

    /**
     * Menambahkan primary key. Boleh lebih dari satu kolom (composite key).
     *
     * Nama constraint BOLEH dikosongkan, dan itu memang beda dari index() /
     * foreign(). Satu tabel hanya punya satu primary key dan di MySQL namanya
     * selalu PRIMARY, jadi tidak ada nama buatan yang perlu diingat saat
     * menghapusnya. Driver yang menuntut nama sendiri (SQL Server) tetap bisa
     * menerima nama bila disebutkan.
     *
     * @param string[] $columns
     */
    public function primary(array $columns, ?string $name = null): static {
        $this->operations[] = [
            'operation' => self::OPERATION_PRIMARY,
            'name' => $name !== null ? $this->requireName($name, 'constraint') : null,
            'columns' => $this->requireColumns($columns, 'Primary key'),
        ];

        return $this;
    }

    /**
     * Menghapus primary key.
     *
     * $name hanya berguna bagi driver yang memerlukannya; MySQL selalu menamai
     * primary key-nya PRIMARY sehingga parameter itu diabaikan di sana.
     */
    public function dropPrimary(?string $name = null): static {
        $this->operations[] = [
            'operation' => self::OPERATION_DROP_PRIMARY,
            'name' => $name !== null ? $this->requireName($name, 'constraint') : null,
        ];

        return $this;
    }

    /**
     * Menambahkan foreign key.
     *
     *     $table->foreign(['idGrup'], 'grup', ['idGrup'], 'fk_kontak_grup', onDelete: 'CASCADE');
     *
     * Nama constraint WAJIB, dengan alasan yang sama seperti index: nama yang
     * dibuat otomatis oleh driver tidak bisa ditebak, padahal nama itulah yang
     * harus disebutkan lagi di dropForeign().
     *
     * @param string[] $columns Kolom di tabel ini.
     * @param string[] $references Kolom yang dirujuk di $foreignTable.
     * @param ?string $onDelete CASCADE, SET NULL, RESTRICT, NO ACTION, atau SET DEFAULT.
     * @param ?string $onUpdate Sama dengan $onDelete.
     */
    public function foreign(
        array $columns,
        string $foreignTable,
        array $references,
        string $name,
        ?string $onDelete = null,
        ?string $onUpdate = null
    ): static {
        $name = $this->requireName($name, 'foreign key');

        $this->operations[] = [
            'operation' => self::OPERATION_FOREIGN,
            'name' => $name,
            'columns' => $this->requireColumns($columns, "Foreign key '{$name}'"),
            'table' => $this->requireName($foreignTable, 'tabel'),
            'references' => $this->requireColumns($references, "Foreign key '{$name}'"),
            'on_delete' => $this->requireAction($onDelete, 'ON DELETE'),
            'on_update' => $this->requireAction($onUpdate, 'ON UPDATE'),
        ];

        return $this;
    }

    /**
     * Menghapus foreign key berdasarkan nama constraint-nya.
     */
    public function dropForeign(string $name): static {
        $this->operations[] = [
            'operation' => self::OPERATION_DROP_FOREIGN,
            'name' => $this->requireName($name, 'foreign key'),
        ];

        return $this;
    }

    public function getTable(): string {
        return $this->table;
    }

    public function getOperations(): array {
        $operations = [];

        foreach($this->operations as $node) {
            // Operasi kolom: add/modify dan posisinya dibaca ulang dari
            // ColumnClause, karena change()/after()/first() bisa dipanggil setelah
            // kolomnya tercatat.
            if(isset($node['column'])) {
                $operations[] = [
                    'operation' => $node['column']->operation(),
                    'column' => $node['column']->toArray(),
                    'position' => $node['column']->position(),
                ];
                continue;
            }

            $operations[] = $node;
        }

        return $operations;
    }

    /**
     * Mencatat satu operasi index.
     *
     * @param string[] $columns
     * @throws InvalidArgumentException Bila kolom atau nama index kosong.
     */
    private function addIndex(array $columns, string $name, bool $unique): static {
        $name = $this->requireName($name, 'index');

        $this->operations[] = [
            'operation' => self::OPERATION_INDEX,
            'name' => $name,
            'columns' => $this->requireColumns($columns, "Index '{$name}'"),
            'unique' => $unique,
        ];

        return $this;
    }

    /**
     * Membersihkan daftar kolom: dipangkas, entri kosongnya dibuang.
     *
     * @param array<int, mixed> $columns
     * @return string[]
     * @throws InvalidArgumentException Bila tidak ada kolom yang tersisa.
     */
    private function requireColumns(array $columns, string $context): array {
        $columns = array_values(array_filter(
            array_map(fn($column) => trim((string) $column), $columns),
            fn(string $column) => $column !== ''
        ));

        if(empty($columns)) {
            throw new InvalidArgumentException("{$context} tidak memiliki kolom.");
        }

        return $columns;
    }

    /**
     * @throws InvalidArgumentException Bila namanya kosong.
     */
    private function requireName(string $name, string $label): string {
        $name = trim($name);

        if($name === '') {
            throw new InvalidArgumentException("Nama {$label} tidak boleh kosong.");
        }

        return $name;
    }

    /**
     * Menormalkan aksi referensial (ON DELETE / ON UPDATE).
     *
     * @throws InvalidArgumentException Bila aksinya tidak dikenal.
     */
    private function requireAction(?string $action, string $label): ?string {
        if($action === null) return null;

        $action = strtoupper(trim($action));

        if(!in_array($action, self::FOREIGN_ACTIONS, true)) {
            throw new InvalidArgumentException("Aksi {$label} '{$action}' tidak dikenal. Pilihan: " . implode(', ', self::FOREIGN_ACTIONS) . '.');
        }

        return $action;
    }

    /**
     * Mencatat satu kolom, lalu mengembalikan ColumnClause-nya supaya modifier
     * bisa dirantai. Satu-satunya tempat yang menyentuh $operations untuk kolom.
     *
     * @param array<string, mixed> $options Nilai awal length/precision/scale/nullable.
     * @throws InvalidArgumentException Bila nama kolom kosong atau duplikat.
     */
    protected function add(string $name, string $type, array $options = []): ColumnClause {
        $column = ColumnClause::make($name, $type, $options);

        foreach($this->operations as $node) {
            if(isset($node['column']) && $node['column']->name() === $column->name()) {
                throw new InvalidArgumentException("Kolom '{$name}' didefinisikan lebih dari sekali pada alter tabel {$this->table}.");
            }
        }

        $this->operations[] = ['column' => $column];

        return $column;
    }

}
