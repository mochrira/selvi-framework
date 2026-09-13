<?php

namespace Selvi\Database\Builder;

use Closure;
use InvalidArgumentException;

class WhereBuilder {

    /**
     * @var WhereClause[]
     */
    protected array $clauses = [];

    public function where($input): static
    {
        return $this->addConditions('AND', $input);
    }

    public function orWhere($input): static
    {
        return $this->addConditions('OR', $input);
    }

    public function hasWheres(): bool
    {
        return !empty($this->clauses);
    }

    /**
     * Struktur kanonik (AST) dari seluruh kondisi.
     *
     * Bentuk inilah kontrak yang dikonsumsi Grammar tiap driver. Rendering SQL
     * (identifier quoting, konversi value menjadi literal) sengaja tidak ada di sini.
     */
    public function toArray(): array
    {
        $nodes = [];

        foreach ($this->clauses as $clause) {
            $nodes[] = $clause->toArray();
        }

        return $nodes;
    }

    protected function addConditions(string $boolean, $input): static
    {
        // (5) Closure → nested where
        if ($input instanceof Closure) {
            $sub = new static();
            $input($sub);

            if ($sub->hasWheres()) { 
                $this->clauses[] = WhereClause::nested($sub, $boolean);
            }
            return $this;
        }

        // (4) String tunggal → raw
        if (is_string($input)) {
            $this->clauses[] = WhereClause::raw($input, $boolean);
            return $this;
        }

        // Array
        if (is_array($input)) {
            if (empty($input)) {
                return $this;
            }

            // (3) Array of strings: ['a = 1', 'b = 2']
            if ($this->isListOfStrings($input)) {
                foreach ($input as $sql) {
                    $this->clauses[] = WhereClause::raw($sql, $boolean);
                }
                return $this;
            }

            // (1) & (2) Array of conditions, boleh campur raw string
            // Contoh: [['a', '=', 1], ['b', '>', 2], 'c IS NOT NULL']
            foreach ($input as $cond) {
                if (is_string($cond)) {
                    $this->clauses[] = WhereClause::raw($cond, $boolean);
                    continue;
                }

                if (!is_array($cond)) {
                    throw new InvalidArgumentException(
                        'Format where tidak valid: hanya string (raw) atau array kondisi yang didukung.'
                    );
                }

                $this->clauses[] = WhereClause::fromCondition($cond, $boolean);
            }
            return $this;
        }

        throw new InvalidArgumentException('Format where tidak dikenali.');
    }

    protected function isListOfStrings(array $arr): bool
    {
        foreach ($arr as $v) {
            if (!is_string($v)) return false;
        }
        return true;
    }

}