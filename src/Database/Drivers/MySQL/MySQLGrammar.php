<?php 

namespace Selvi\Database\Drivers\MySQL;

use InvalidArgumentException;
use RuntimeException;
use Selvi\Database\Contracts\GrammarInterface;
use Selvi\Database\QueryBuilder;

class MySQLGrammar implements GrammarInterface {

    /**
     * Susun query SELECT dari state QueryBuilder.
     *
     * @return array{0: string, 1: array} [SQL, parameter]
     */
    public function compileSelect(QueryBuilder $queryBuilder): array
    {
        $table = $this->requireTable($queryBuilder);
        $columns = $queryBuilder->getColumns();
        $columnsSql = is_array($columns) ? implode(', ', $columns) : $columns;

        [$whereSql, $whereParams] = $this->compileWheres($queryBuilder, false);
        $limitOffsetSql = $this->compileLimitOffset($queryBuilder);

        return ["SELECT {$columnsSql} FROM {$table}{$whereSql}{$limitOffsetSql}", $whereParams];
    }

    /**
     * Susun query INSERT.
     *
     * @return array{0: string, 1: array} [SQL, parameter]
     */
    public function compileInsert(QueryBuilder $queryBuilder, array $data): array
    {
        $table = $this->requireTable($queryBuilder);
        if ($data === []) {
            throw new InvalidArgumentException('Data untuk INSERT tidak boleh kosong.');
        }

        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        return ["INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})", array_values($data)];
    }

    /**
     * Susun query UPDATE (menggunakan WHERE dari QueryBuilder).
     *
     * @return array{0: string, 1: array} [SQL, parameter]
     */
    public function compileUpdate(QueryBuilder $queryBuilder, array $data): array
    {
        $table = $this->requireTable($queryBuilder);
        if ($data === []) {
            throw new InvalidArgumentException('Data untuk UPDATE tidak boleh kosong.');
        }

        $set = implode(', ', array_map(fn(string $column): string => "{$column} = ?", array_keys($data)));
        [$whereSql, $whereParams] = $this->compileWheres($queryBuilder, true);

        return [
            "UPDATE {$table} SET {$set}{$whereSql}",
            [...array_values($data), ...$whereParams]
        ];
    }

    /**
     * Susun query DELETE (menggunakan WHERE dari QueryBuilder).
     *
     * @return array{0: string, 1: array} [SQL, parameter]
     */
    public function compileDelete(QueryBuilder $queryBuilder): array
    {
        $table = $this->requireTable($queryBuilder);
        [$whereSql, $whereParams] = $this->compileWheres($queryBuilder, true);

        return ["DELETE FROM {$table}{$whereSql}", $whereParams];
    }

    private function requireTable(QueryBuilder $queryBuilder): string
    {
        $table = $queryBuilder->getTable();
        if ($table === null) {
            throw new RuntimeException('Tabel belum ditentukan. Panggil table() terlebih dahulu.');
        }
        return $table;
    }

    /**
     * Susun fragmen WHERE beserta parameternya dari QueryBuilder.
     *
     * @return array{0: string, 1: array} [fragmen SQL WHERE (placeholder ?), nilai yang di-bind]
     */
    private function compileWheres(QueryBuilder $queryBuilder, bool $required): array
    {
        $wheres = $queryBuilder->getWheres();
        if ($wheres === []) {
            if ($required) {
                throw new RuntimeException('Operasi ini wajib disertai kondisi WHERE. Panggil where() terlebih dahulu.');
            }
            return ['', []];
        }

        $parts = [];
        $params = [];
        foreach ($wheres as [$column, $operator, $value]) {
            $parts[] = "{$column} {$operator} ?";
            $params[] = $value;
        }

        return [' WHERE '.implode(' AND ', $parts), $params];
    }

    /**
     * Susun klausa LIMIT dan OFFSET untuk MySQL.
     * Di MySQL, klausa OFFSET wajib disertai dengan LIMIT.
     */
    private function compileLimitOffset(QueryBuilder $queryBuilder): string
    {
        $limit = $queryBuilder->getLimit();
        $offset = $queryBuilder->getOffset();

        if ($offset !== null && $limit === null) {
            throw new RuntimeException('Operasi OFFSET pada MySQL wajib disertai dengan LIMIT. Panggil limit() terlebih dahulu.');
        }

        $sql = '';
        if ($limit !== null) {
            $sql .= " LIMIT {$limit}";
            if ($offset !== null) {
                $sql .= " OFFSET {$offset}";
            }
        }

        return $sql;
    }

}
