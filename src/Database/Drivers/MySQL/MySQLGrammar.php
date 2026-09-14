<?php 

namespace Selvi\Database\Drivers\MySQL;

use InvalidArgumentException;
use Selvi\Database\Builder\WhereClause;
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

        $limit = $this->compileLimit($builder->getLimit(), $builder->getOffset());
        
        return join(" ", array_filter([$select, $from, $join, $where, $group, $limit], function ($part) {
            return $part !== '';
        }));
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