<?php 

namespace Selvi\Database\Drivers\MySQL;

use InvalidArgumentException;
use Selvi\Database\Builder\DML\Clauses\WhereClause;
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

}