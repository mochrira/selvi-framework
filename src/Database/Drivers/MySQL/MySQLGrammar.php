<?php 

namespace Selvi\Database\Drivers\MySQL;

use InvalidArgumentException;
use Selvi\Database\Builder\DDL\AlterBlueprint;
use Selvi\Database\Builder\DDL\Clauses\ColumnClause;
use Selvi\Database\Builder\DML\Clauses\WhereClause;
use Selvi\Database\Contracts\AlterBlueprintInterface;
use Selvi\Database\Contracts\BlueprintInterface;
use Selvi\Database\Contracts\GrammarInterface;
use Selvi\Database\Contracts\QueryBuilderInterface;
use Selvi\Database\Contracts\SanitizerInterface;

class MySQLGrammar implements GrammarInterface {

    private SanitizerInterface $sanitizer;

    function __construct(?SanitizerInterface $sanitizer = null) {
        $this->sanitizer = $sanitizer ?? new MySQLSanitizer();
    }

    function compileSelect(QueryBuilderInterface $builder) : string {
        $from = "FROM {$builder->getTable()}";

        $columns = count($builder->getColumns()) == 0 ? ['*'] : $builder->getColumns();
        $select = "SELECT " . join(', ', $columns);

        $joins = $builder->joins();
        $join = empty($joins) ? "" : $this->compileJoins($joins);

        $wheres = $builder->wheres();
        $where = empty($wheres) ? "" : "WHERE " . $this->compileWhere($wheres);

        $groups = $builder->groups();
        $group = empty($groups) ? "" : $this->compileGroups($groups);

        $orders = $builder->orders();
        $order = empty($orders) ? "" : $this->compileOrders($orders);

        $limit = $this->compileLimit($builder->getLimit(), $builder->getOffset());
        
        return join(" ", array_filter([$select, $from, $join, $where, $group, $order, $limit], function ($part) {
            return $part !== '';
        }));
    }

    public function compileInsert(string $table, array $columns, array $rows): string
    {
        $table = trim($table, '` ');
        if ($table === '') {
            throw new InvalidArgumentException('Nama tabel untuk insert tidak boleh kosong.');
        }

        if (empty($columns)) {
            throw new InvalidArgumentException('Daftar kolom untuk insert tidak boleh kosong.');
        }

        if (empty($rows)) {
            throw new InvalidArgumentException('Baris data untuk insert tidak boleh kosong.');
        }

        $columnList = implode(', ', array_map(fn($col) => "`" . trim($col, '` ') . "`", $columns));

        $valuesList = [];
        foreach ($rows as $row) {
            $rowValues = [];
            foreach ($columns as $column) {
                $val = $row[$column] ?? null;
                $rowValues[] = $this->sanitizer->sanitize($val);
            }
            $valuesList[] = '(' . implode(', ', $rowValues) . ')';
        }

        $valuesStr = implode(', ', $valuesList);

        return "INSERT INTO `{$table}` ({$columnList}) VALUES {$valuesStr}";
    }

    public function compileUpdate(string $table, array $values, array $wheres): string
    {
        $table = trim($table, '` ');
        if ($table === '') {
            throw new InvalidArgumentException('Nama tabel untuk update tidak boleh kosong.');
        }

        if (empty($values)) {
            throw new InvalidArgumentException('Data kolom untuk update tidak boleh kosong.');
        }

        if (empty($wheres)) {
            throw new InvalidArgumentException('Operasi UPDATE memerlukan setidaknya satu kondisi WHERE.');
        }

        $setParts = [];
        foreach ($values as $column => $val) {
            $col = "`" . trim((string)$column, '` ') . "`";
            $setParts[] = "{$col} = " . $this->sanitizer->sanitize($val);
        }

        $setStr = implode(', ', $setParts);
        $whereStr = $this->compileWhere($wheres);

        return "UPDATE `{$table}` SET {$setStr} WHERE {$whereStr}";
    }

    public function compileDelete(string $table, array $wheres): string
    {
        $table = trim($table, '` ');
        if ($table === '') {
            throw new InvalidArgumentException('Nama tabel untuk delete tidak boleh kosong.');
        }

        if (empty($wheres)) {
            throw new InvalidArgumentException('Operasi DELETE memerlukan setidaknya satu kondisi WHERE.');
        }

        $whereStr = $this->compileWhere($wheres);

        return "DELETE FROM `{$table}` WHERE {$whereStr}";
    }


    /**
     * Merender daftar JOIN menjadi SQL MySQL.
     *
     * Klausa ON sudah berupa string mentah dari JoinClause, jadi tidak ada
     * sanitasi/normalisasi di sini.
     */
    protected function compileJoins(array $joins): string
    {
        $parts = [];

        foreach ($joins as $j) {
            $parts[] = "{$j['join']} JOIN {$j['table']} ON {$j['on']}";
        }

        return implode(' ', $parts);
    }

    /**
     * Merender struktur kanonik (AST) where menjadi SQL MySQL.
     *
     * Method ini sengaja tidak menyentuh state builder: input sama selalu
     * menghasilkan output yang sama (idempotent).
     */
    protected function compileWhere(array $wheres): string
    {
        // var_dump($wheres);
        if (empty($wheres)) {
            return '';
        }

        $parts = [];
        foreach ($wheres as $i => $w) {
            $clause = $this->compileWhereClause($w);

            // Kondisi pertama tidak butuh prefix boolean
            $parts[] = ($i === 0) ? $clause : ($w['boolean'] . ' ' . $clause);
        }

        return implode(' ', $parts);
    }

    /**
     * Merender satu node AST menjadi SQL MySQL.
     *
     * Selama isi tiap arm masih satu ekspresi, semuanya ditulis inline di sini.
     * Ekstrak ke method sendiri begitu satu tipe butuh percabangan (mis. saat
     * value majemuk seperti IN (...) mulai didukung).
     */
    protected function compileWhereClause(array $w): string
    {
        return match ($w['type']) {
            WhereClause::TYPE_RAW    => $w['sql'],
            WhereClause::TYPE_NULL   => $w['column'] . ' ' . $w['operator'],
            WhereClause::TYPE_BASIC  => $w['column'] . ' ' . $w['operator'] . ' ' . $this->sanitizer->sanitize($w['value']),
            WhereClause::TYPE_NESTED => '(' . $this->compileWhere($w['wheres']) . ')',
            default => throw new InvalidArgumentException(
                'Tipe kondisi tidak dikenal: ' . $w['type']
            ),
        };
    }

    /**
     * Merender klausa GROUP BY menjadi SQL MySQL.
     *
     * @param string[] $groups
     */
    protected function compileGroups(array $groups): string
    {
        if (empty($groups)) {
            return '';
        }
        return "GROUP BY " . join(', ', $groups);
    }

    /**
     * Merender klausa ORDER BY menjadi SQL MySQL.
     *
     * @param array<int, array{column: string, direction: string}> $orders
     */
    protected function compileOrders(array $orders): string
    {
        if (empty($orders)) {
            return '';
        }

        $parts = [];
        foreach ($orders as $o) {
            $parts[] = "{$o['column']} {$o['direction']}";
        }

        return "ORDER BY " . implode(', ', $parts);
    }

    /**
     * Merender klausa LIMIT dan OFFSET menjadi SQL MySQL.
     */
    protected function compileLimit(?int $limit, ?int $offset = null): string
    {
        if ($limit === null || $limit < 0) {
            return '';
        }

        $sql = "LIMIT " . $limit;
        if ($offset !== null && $offset > 0) {
            $sql .= " OFFSET " . $offset;
        }

        return $sql;
    }

    /**
     * Merender CREATE TABLE dari struktur kanonik Blueprint.
     *
     * Di sinilah perbedaan dialek ditangani: tipe semantik menjadi tipe MySQL,
     * flag auto_increment menjadi AUTO_INCREMENT, dan seterusnya. Blueprint
     * sendiri tetap netral driver.
     */
    public function compileCreateTable(BlueprintInterface $blueprint) : string
    {
        $columns = [];

        foreach($blueprint->getColumns() as $column) {
            $columns[] = $this->compileColumn($column);
        }

        $ifNotExists = $blueprint->hasIfNotExists() ? 'IF NOT EXISTS ' : '';

        return "CREATE TABLE {$ifNotExists}{$blueprint->getTable()} (" . implode(', ', $columns) . ')';
    }

    /**
     * Merender satu kolom CREATE TABLE.
     *
     * @param array<string, mixed> $column Node kolom kanonik dari Blueprint.
     */
    protected function compileColumn(array $column) : string
    {
        $sql = "{$column['name']} " . $this->compileColumnType($column);

        if(!$column['nullable']) $sql .= ' NOT NULL';
        if($column['default'] !== null) $sql .= ' DEFAULT ' . $this->sanitizer->sanitize($column['default']);
        if($column['auto_increment']) $sql .= ' AUTO_INCREMENT';
        if($column['key']) $sql .= ' PRIMARY KEY';
        if($column['unique']) $sql .= ' UNIQUE';

        return $sql;
    }

    /**
     * Menerjemahkan tipe kolom semantik menjadi tipe MySQL.
     *
     * Tipe yang tidak dikenal dilempar sebagai exception, sama seperti
     * compileWhereClause — supaya driver yang belum mendukung tipe baru gagal
     * dengan jelas alih-alih menghasilkan SQL yang salah.
     *
     * @param array<string, mixed> $column Node kolom kanonik dari Blueprint.
     */
    protected function compileColumnType(array $column) : string
    {
        return match($column['type']) {
            'integer'    => 'INT',
            'bigInteger' => 'BIGINT',
            'string'     => 'VARCHAR(' . ($column['length'] ?? 255) . ')',
            'text'       => 'TEXT',
            'boolean'    => 'TINYINT(1)',
            'decimal'    => 'DECIMAL(' . ($column['precision'] ?? 10) . ', ' . ($column['scale'] ?? 0) . ')',
            'float'      => 'DOUBLE',
            'date'       => 'DATE',
            'datetime'   => 'DATETIME',
            default      => throw new InvalidArgumentException(
                'Tipe kolom tidak dikenal: ' . $column['type']
            ),
        };
    }

    /**
     * Merender DROP TABLE.
     *
     * MySQL mendukung "IF EXISTS" langsung, jadi tidak ada trik khusus di sini;
     * yang berbeda antar driver (mis. SQL Server) akan ditangani implementasinya
     * masing-masing, bukan di Blueprint.
     */
    public function compileDropTable(string $table, bool $ifExists) : string
    {
        $ifExists = $ifExists ? 'IF EXISTS ' : '';

        return "DROP TABLE {$ifExists}{$table}";
    }

    /**
     * Merender RENAME TABLE.
     *
     * Memakai bentuk ANSI "ALTER TABLE ... RENAME TO ..." yang didukung MySQL,
     * supaya padanannya di driver lain terbaca lebih dekat (PostgreSQL sama,
     * SQL Server memakai sp_rename).
     */
    public function compileRenameTable(string $from, string $to) : string
    {
        return "ALTER TABLE {$from} RENAME TO {$to}";
    }

    /**
     * Merender TRUNCATE TABLE.
     */
    public function compileTruncateTable(string $table) : string
    {
        return "TRUNCATE TABLE {$table}";
    }

    /**
     * Merender ALTER TABLE.
     *
     * MySQL bisa menggabungkan semua perubahan ke dalam SATU statement, jadi
     * hasilnya selalu string — perbedaan dengan driver yang butuh beberapa
     * statement berada di implementasi masing-masing, bukan di sini.
     */
    public function compileAlterTable(AlterBlueprintInterface $blueprint) : string|array
    {
        $clauses = [];

        foreach($blueprint->getOperations() as $operation) {
            $clauses[] = $this->compileAlterOperation($operation);
        }

        return "ALTER TABLE {$blueprint->getTable()} " . implode(', ', $clauses);
    }

    /**
     * Merender satu operasi alter.
     *
     * MODIFY memakai compileColumn() yang sama dengan CREATE TABLE, karena MySQL
     * memang menuntut definisi kolom lengkap: atribut yang tidak disebut akan
     * hilang dari kolom tersebut.
     *
     * @param array<string, mixed> $operation Node kanonik dari AlterBlueprintInterface.
     */
    protected function compileAlterOperation(array $operation) : string
    {
        return match($operation['operation']) {
            ColumnClause::OPERATION_ADD      => 'ADD COLUMN ' . $this->compileColumn($operation['column']) . $this->compileAlterPosition($operation['position']),
            ColumnClause::OPERATION_MODIFY   => 'MODIFY COLUMN ' . $this->compileColumn($operation['column']) . $this->compileAlterPosition($operation['position']),
            AlterBlueprint::OPERATION_DROP   => 'DROP COLUMN ' . $operation['name'],
            AlterBlueprint::OPERATION_RENAME => "RENAME COLUMN {$operation['from']} TO {$operation['to']}",
            AlterBlueprint::OPERATION_INDEX  => 'ADD ' . ($operation['unique'] ? 'UNIQUE ' : '') . "INDEX {$operation['name']} (" . implode(', ', $operation['columns']) . ')',
            AlterBlueprint::OPERATION_DROP_INDEX => 'DROP INDEX ' . $operation['name'],
            AlterBlueprint::OPERATION_PRIMARY => $this->compileAlterPrimary($operation),
            AlterBlueprint::OPERATION_DROP_PRIMARY => 'DROP PRIMARY KEY',
            AlterBlueprint::OPERATION_FOREIGN => $this->compileAlterForeignKey($operation),
            AlterBlueprint::OPERATION_DROP_FOREIGN => 'DROP FOREIGN KEY ' . $operation['name'],
            default => throw new InvalidArgumentException(
                'Operasi alter tidak dikenal: ' . $operation['operation']
            ),
        };
    }

    /**
     * Merender penambahan primary key.
     *
     * Nama constraint opsional: MySQL menerima ADD PRIMARY KEY tanpa nama karena
     * primary key-nya memang selalu bernama PRIMARY.
     *
     * @param array<string, mixed> $operation Node kanonik dari AlterBlueprintInterface.
     */
    protected function compileAlterPrimary(array $operation) : string
    {
        $constraint = $operation['name'] !== null ? "CONSTRAINT {$operation['name']} " : '';

        return 'ADD ' . $constraint . 'PRIMARY KEY (' . implode(', ', $operation['columns']) . ')';
    }

    /**
     * Merender penambahan foreign key.
     *
     * Beda antar driver bukan pada bentuk ADD-nya (hampir seragam), melainkan pada
     * cara menghapusnya: MySQL memakai DROP FOREIGN KEY, SQL Server DROP CONSTRAINT.
     *
     * @param array<string, mixed> $operation Node kanonik dari AlterBlueprintInterface.
     */
    protected function compileAlterForeignKey(array $operation) : string
    {
        $sql = "ADD CONSTRAINT {$operation['name']} FOREIGN KEY (" . implode(', ', $operation['columns']) . ')';
        $sql .= " REFERENCES {$operation['table']} (" . implode(', ', $operation['references']) . ')';

        if($operation['on_delete'] !== null) $sql .= " ON DELETE {$operation['on_delete']}";
        if($operation['on_update'] !== null) $sql .= " ON UPDATE {$operation['on_update']}";

        return $sql;
    }

    /**
     * Merender hint posisi kolom.
     *
     * Diterapkan pada ADD COLUMN dan MODIFY COLUMN — MySQL memakai AFTER/FIRST
     * untuk menempatkan kolom, termasuk memindahkan kolom yang sudah ada lewat
     * MODIFY. Tanpa posisi, klausanya kosong.
     *
     * @param array<string, mixed>|null $position Node dari AlterBlueprintInterface.
     */
    protected function compileAlterPosition(?array $position) : string
    {
        if($position === null) return '';

        if($position['first'] ?? false) return ' FIRST';

        return " AFTER {$position['after']}";
    }

}