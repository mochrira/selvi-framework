<?php

declare(strict_types=1);

namespace Selvi\Database\Drivers\PostgreSQL;

use InvalidArgumentException;
use Selvi\Database\Builder\DDL\AlterBlueprint;
use Selvi\Database\Builder\DDL\Clauses\ColumnClause;
use Selvi\Database\Builder\DML\Clauses\SelectNode;
use Selvi\Database\Builder\DML\Clauses\WhereClause;
use Selvi\Database\Interfaces\IAlterBlueprint;
use Selvi\Database\Interfaces\IBlueprint;
use Selvi\Database\Interfaces\IGrammar;
use Selvi\Database\Interfaces\IQueryBuilder;
use Selvi\Database\Interfaces\ISanitizer;

/**
 * Grammar PostgreSQL.
 *
 * Struktur DML-nya mengikuti MySQLGrammar karena bentuk SQL-nya memang sama;
 * yang berbeda adalah dialek DDL/ALTER, literal boolean, dan penulisan
 * identifier (tanpa backtick).
 *
 * Identifier ditulis apa adanya, sama seperti jalur SELECT/WHERE yang sudah ada.
 * Konsekuensinya PostgreSQL melakukan fold ke lowercase, dan nama kolom yang
 * merupakan reserved word harus dihindari.
 */
class PostgreSQLGrammar implements IGrammar {

    /**
     * Start of Evaluated
     */

    function compileSelect(IQueryBuilder $builder): string {
        $from = "FROM {$builder->getTable()}";

        $select = "SELECT ";
        $columns = $builder->getSelectBuilder()->toArray();
        if(empty($columns)) {
            $select .= "*";
        } else {
            $select .= join(", ", array_map(
                fn($column) => $this->compileSelectNode($column),
                $columns
            ));
        }

        $joins = $builder->getJoinBuilder()->toArray();
        $join = empty($joins) ? "" : $this->compileJoins($joins);

        $wheres = $builder->getWhereClauses();
        $where = empty($wheres) ? "" : "WHERE " . $this->compileWhere($wheres);

        $orders = $builder->getOrderBuilder()->toArray();
        $order = empty($orders) ? "" : $this->compileOrder($orders);

        $limit = $this->compileLimit($builder->getLimit(), $builder->getOffset());

        $groups = $builder->getGroupBuilder()->toArray();
        $group = empty($groups) ? "" : $this->compileGroups($groups);

        return join(" ", array_filter([
            $select,
            $from,
            $join,
            $where,
            $group,
            $order,
            $limit
        ], function ($part) {
            return $part !== '';
        }));
    }

    /**
     * Merender satu node SELECT (kolom, literal, fungsi, atau raw).
     */
    private function compileSelectNode(array $node, bool $withAlias = true): string {
        $type = $node['type'] ?? SelectNode::TYPE_COLUMN;

        $sql = match ($type) {
            SelectNode::TYPE_COLUMN => $this->compileColumnNode($node),
            SelectNode::TYPE_LITERAL => $this->sanitizer->sanitize($node['value'] ?? null),
            SelectNode::TYPE_FUNCTION => $this->compileFunctionNode($node),
            SelectNode::TYPE_RAW => $this->compileRawNode($node),
            default => throw new InvalidArgumentException(
                "Tipe select node '{$type}' tidak dikenali."
            ),
        };

        if($withAlias && isset($node['alias']) && $node['alias'] !== null) {
            $sql .= " AS {$node['alias']}";
        }

        return $sql;
    }

    private function compileColumnNode(array $node): string {
        $column = trim((string) ($node['column'] ?? ''));
        if($column === '') {
            throw new InvalidArgumentException('Nama kolom select tidak boleh kosong.');
        }

        $table = $node['table'] ?? null;
        if($table !== null && trim((string) $table) !== '') {
            return trim((string) $table) . ".{$column}";
        }

        return $column;
    }

    private function compileFunctionNode(array $node): string {
        $name = trim((string) ($node['name'] ?? ''));
        if(!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
            throw new InvalidArgumentException("Nama fungsi SQL '{$name}' tidak valid.");
        }

        $arguments = $node['arguments'] ?? [];
        if(!is_array($arguments)) {
            throw new InvalidArgumentException('Argumen fungsi SQL harus berupa array node.');
        }

        $compiled = [];
        foreach($arguments as $argument) {
            if(!is_array($argument)) {
                throw new InvalidArgumentException('Argumen fungsi SQL harus berupa node select.');
            }
            $compiled[] = $this->compileSelectNode($argument, false);
        }

        return strtoupper($name) . '(' . implode(', ', $compiled) . ')';
    }

    private function compileRawNode(array $node): string {
        $sql = trim((string) ($node['sql'] ?? ''));
        if($sql === '') {
            throw new InvalidArgumentException('Raw select tidak boleh kosong.');
        }

        return $sql;
    }

    public function compileInsert(string $table, array $columns, array $rows): string {
        $table = trim($table);
        if ($table === '') {
            throw new InvalidArgumentException('Nama tabel untuk insert tidak boleh kosong.');
        }

        if (empty($columns)) {
            throw new InvalidArgumentException('Daftar kolom untuk insert tidak boleh kosong.');
        }

        if (empty($rows)) {
            throw new InvalidArgumentException('Baris data untuk insert tidak boleh kosong.');
        }

        $columnList = implode(', ', array_map(fn($col) => trim((string) $col), $columns));

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

        return "INSERT INTO {$table} ({$columnList}) VALUES {$valuesStr}";
    }

    public function compileUpdate(string $table, array $values, array $wheres): string {
        $table = trim($table);
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
            $col = trim((string) $column);
            $setParts[] = "{$col} = " . $this->sanitizer->sanitize($val);
        }

        $setStr = implode(', ', $setParts);
        $whereStr = $this->compileWhere($wheres);

        return "UPDATE {$table} SET {$setStr} WHERE {$whereStr}";
    }

    public function compileDelete(string $table, array $wheres): string {
        $table = trim($table);
        if ($table === '') {
            throw new InvalidArgumentException('Nama tabel untuk delete tidak boleh kosong.');
        }

        if (empty($wheres)) {
            throw new InvalidArgumentException('Operasi DELETE memerlukan setidaknya satu kondisi WHERE.');
        }

        $whereStr = $this->compileWhere($wheres);

        return "DELETE FROM {$table} WHERE {$whereStr}";
    }

    protected function compileWhere(array $wheres): string {
        if (empty($wheres)) {
            return '';
        }

        $parts = [];
        foreach ($wheres as $i => $w) {
            $clause = $this->compileWhereClause($w);
            $parts[] = ($i === 0) ? $clause : ($w['boolean'] . ' ' . $clause);
        }

        return implode(' ', $parts);
    }

    protected function compileWhereClause(array $w): string {
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

    protected function compileLimit(?int $limit, ?int $offset = null): string {
        if ($limit === null || $limit < 0) {
            return '';
        }

        $sql = "LIMIT " . $limit;
        if ($offset !== null && $offset > 0) {
            $sql .= " OFFSET " . $offset;
        }

        return $sql;
    }

    protected function compileOrder(array $orders): string {
        return "ORDER BY " . implode(', ', $orders);
    }

    /**
     * End of Evaluated
     */

    private ISanitizer $sanitizer;

    function __construct(?ISanitizer $sanitizer = null) {
        $this->sanitizer = $sanitizer ?? new PostgreSQLSanitizer();
    }

    /**
     * Merender daftar JOIN menjadi SQL PostgreSQL.
     *
     * Klausa ON sudah berupa string mentah dari JoinClause, jadi tidak ada
     * sanitasi/normalisasi di sini.
     */
    protected function compileJoins(array $joins): string {
        $parts = [];

        foreach ($joins as $j) {
            $parts[] = "{$j['join']} JOIN {$j['table']} ON {$j['on']}";
        }

        return implode(' ', $parts);
    }

    /**
     * Merender klausa GROUP BY.
     *
     * @param string[] $groups
     */
    protected function compileGroups(array $groups): string {
        if (empty($groups)) {
            return '';
        }
        return "GROUP BY " . join(', ', $groups);
    }

    /**
     * Merender CREATE TABLE dari struktur kanonik Blueprint.
     */
    public function compileCreateTable(IBlueprint $blueprint): string {
        $columns = [];

        foreach ($blueprint->getColumns() as $column) {
            $columns[] = $this->compileColumn($column);
        }

        $ifNotExists = $blueprint->hasIfNotExists() ? 'IF NOT EXISTS ' : '';

        return "CREATE TABLE {$ifNotExists}{$blueprint->getTable()} (" . implode(', ', $columns) . ')';
    }

    /**
     * Merender satu kolom CREATE TABLE.
     *
     * DEFAULT dilewati bila kolom auto increment: PostgreSQL menolak kolom yang
     * punya DEFAULT sekaligus IDENTITY.
     *
     * @param array<string, mixed> $column Node kolom kanonik dari Blueprint.
     */
    protected function compileColumn(array $column): string {
        $sql = "{$column['name']} " . $this->compileColumnType($column);

        if (!$column['auto_increment'] && $column['default'] !== null) {
            $sql .= ' DEFAULT ' . $this->sanitizer->sanitize($column['default']);
        }
        if ($column['auto_increment']) $sql .= ' GENERATED BY DEFAULT AS IDENTITY';
        if (!$column['nullable']) $sql .= ' NOT NULL';
        if ($column['key']) $sql .= ' PRIMARY KEY';
        if ($column['unique']) $sql .= ' UNIQUE';

        return $sql;
    }

    /**
     * Menerjemahkan tipe kolom semantik menjadi tipe PostgreSQL.
     *
     * @param array<string, mixed> $column Node kolom kanonik dari Blueprint.
     */
    protected function compileColumnType(array $column): string {
        return match ($column['type']) {
            'integer'    => 'INTEGER',
            'bigInteger' => 'BIGINT',
            'string'     => 'VARCHAR(' . ($column['length'] ?? 255) . ')',
            'text'       => 'TEXT',
            'boolean'    => 'BOOLEAN',
            'decimal'    => 'NUMERIC(' . ($column['precision'] ?? 10) . ', ' . ($column['scale'] ?? 0) . ')',
            'float'      => 'DOUBLE PRECISION',
            'date'       => 'DATE',
            'time'       => 'TIME',
            'datetime'   => 'TIMESTAMP',
            default      => throw new InvalidArgumentException(
                'Tipe kolom tidak dikenal: ' . $column['type']
            ),
        };
    }

    public function compileDropTable(string $table, bool $ifExists): string {
        $ifExists = $ifExists ? 'IF EXISTS ' : '';

        return "DROP TABLE {$ifExists}{$table}";
    }

    public function compileRenameTable(string $from, string $to): string {
        return "ALTER TABLE {$from} RENAME TO {$to}";
    }

    /**
     * Merender TRUNCATE TABLE.
     *
     * RESTART IDENTITY dipakai agar sequence kembali ke awal, sama seperti
     * perilaku TRUNCATE di MySQL.
     */
    public function compileTruncateTable(string $table): string {
        return "TRUNCATE TABLE {$table} RESTART IDENTITY";
    }

    /**
     * Merender ALTER TABLE.
     *
     * PostgreSQL menggabungkan banyak aksi ke satu ALTER TABLE, tetapi CREATE
     * INDEX / DROP INDEX adalah statement terpisah. Karena itu hasilnya selalu
     * array dan urutannya dipertahankan: aksi ALTER dikumpulkan, lalu dijalankan
     * (flush) sebelum statement mandiri berikutnya.
     */
    public function compileAlterTable(IAlterBlueprint $blueprint): string|array {
        $table = $blueprint->getTable();
        $statements = [];
        $actions = [];

        foreach ($blueprint->getOperations() as $operation) {
            $compiled = $this->compileAlterOperation($table, $operation);

            if ($compiled['standalone']) {
                if (!empty($actions)) {
                    $statements[] = "ALTER TABLE {$table} " . implode(', ', $actions);
                    $actions = [];
                }
                foreach ($compiled['sql'] as $statement) {
                    $statements[] = $statement;
                }
                continue;
            }

            foreach ($compiled['sql'] as $action) {
                $actions[] = $action;
            }
        }

        if (!empty($actions)) {
            $statements[] = "ALTER TABLE {$table} " . implode(', ', $actions);
        }

        return $statements;
    }

    /**
     * Merender satu operasi alter.
     *
     * 'standalone' menandai SQL yang tidak boleh digabung ke ALTER TABLE
     * (CREATE INDEX / DROP INDEX).
     *
     * @param array<string, mixed> $operation Node kanonik dari AlterBlueprint.
     * @return array{standalone: bool, sql: string[]}
     */
    protected function compileAlterOperation(string $table, array $operation): array {
        return match ($operation['operation']) {
            ColumnClause::OPERATION_ADD => [
                'standalone' => false,
                'sql' => ['ADD COLUMN ' . $this->compileColumn($operation['column'])],
            ],
            ColumnClause::OPERATION_MODIFY => [
                'standalone' => false,
                'sql' => $this->compileAlterModify($operation['column']),
            ],
            AlterBlueprint::OPERATION_DROP => [
                'standalone' => false,
                'sql' => ['DROP COLUMN ' . $operation['name']],
            ],
            // RENAME COLUMN adalah bentuk ALTER TABLE tersendiri dan tidak boleh
            // digabung dengan sub-aksi lain dalam satu statement.
            AlterBlueprint::OPERATION_RENAME => [
                'standalone' => true,
                'sql' => ["ALTER TABLE {$table} RENAME COLUMN {$operation['from']} TO {$operation['to']}"],
            ],
            AlterBlueprint::OPERATION_INDEX => [
                'standalone' => true,
                'sql' => [
                    'CREATE ' . ($operation['unique'] ? 'UNIQUE ' : '')
                        . "INDEX {$operation['name']} ON {$table} ("
                        . implode(', ', $operation['columns']) . ')',
                ],
            ],
            AlterBlueprint::OPERATION_DROP_INDEX => [
                'standalone' => true,
                'sql' => ['DROP INDEX ' . $operation['name']],
            ],
            AlterBlueprint::OPERATION_PRIMARY => [
                'standalone' => false,
                'sql' => [$this->compileAlterPrimary($operation)],
            ],
            AlterBlueprint::OPERATION_DROP_PRIMARY => [
                'standalone' => false,
                'sql' => ['DROP CONSTRAINT ' . ($operation['name'] ?? "{$table}_pkey")],
            ],
            AlterBlueprint::OPERATION_FOREIGN => [
                'standalone' => false,
                'sql' => [$this->compileAlterForeignKey($operation)],
            ],
            AlterBlueprint::OPERATION_DROP_FOREIGN => [
                'standalone' => false,
                'sql' => ['DROP CONSTRAINT ' . $operation['name']],
            ],
            default => throw new InvalidArgumentException(
                'Operasi alter tidak dikenal: ' . $operation['operation']
            ),
        };
    }

    /**
     * Menerjemahkan kolom hasil change() menjadi aksi ALTER COLUMN.
     *
     * Berbeda dari MySQL MODIFY, PostgreSQL memecah definisi kolom menjadi
     * beberapa sub-aksi. IDENTITY hanya ditambahkan bila autoIncrement()
     * diminta; identity yang sudah ada sengaja tidak dihapus, karena
     * menghapusnya juga menghapus sequence-nya.
     *
     * @param array<string, mixed> $column Node kolom kanonik.
     * @return string[]
     */
    protected function compileAlterModify(array $column): array {
        $name = $column['name'];
        $actions = [];

        $actions[] = "ALTER COLUMN {$name} TYPE " . $this->compileColumnType($column);

        $actions[] = $column['nullable']
            ? "ALTER COLUMN {$name} DROP NOT NULL"
            : "ALTER COLUMN {$name} SET NOT NULL";

        $actions[] = $column['default'] !== null
            ? "ALTER COLUMN {$name} SET DEFAULT " . $this->sanitizer->sanitize($column['default'])
            : "ALTER COLUMN {$name} DROP DEFAULT";

        if ($column['auto_increment']) {
            $actions[] = "ALTER COLUMN {$name} ADD GENERATED BY DEFAULT AS IDENTITY";
        }

        if ($column['key']) {
            $actions[] = 'ADD PRIMARY KEY (' . $name . ')';
        }

        if ($column['unique']) {
            $actions[] = 'ADD UNIQUE (' . $name . ')';
        }

        return $actions;
    }

    /**
     * Merender penambahan primary key.
     *
     * @param array<string, mixed> $operation Node kanonik dari AlterBlueprint.
     */
    protected function compileAlterPrimary(array $operation): string {
        $constraint = $operation['name'] !== null ? "CONSTRAINT {$operation['name']} " : '';

        return 'ADD ' . $constraint . 'PRIMARY KEY (' . implode(', ', $operation['columns']) . ')';
    }

    /**
     * Merender penambahan foreign key.
     *
     * @param array<string, mixed> $operation Node kanonik dari AlterBlueprint.
     */
    protected function compileAlterForeignKey(array $operation): string {
        $sql = "ADD CONSTRAINT {$operation['name']} FOREIGN KEY (" . implode(', ', $operation['columns']) . ')';
        $sql .= " REFERENCES {$operation['table']} (" . implode(', ', $operation['references']) . ')';

        if ($operation['on_delete'] !== null) $sql .= " ON DELETE {$operation['on_delete']}";
        if ($operation['on_update'] !== null) $sql .= " ON UPDATE {$operation['on_update']}";

        return $sql;
    }
}
