<?php

namespace Selvi\Database\Builder\DML;

use InvalidArgumentException;

class GroupBuilder
{
    /** @var string[] */
    protected array $groups = [];

    public function group(...$columns): static
    {
        // Variadic: group('a', 'b')
        if (count($columns) > 1) {
            foreach ($columns as $col) {
                $this->addColumn($col);
            }
            return $this;
        }

        $input = $columns[0] ?? null;

        // group(['a', 'b'])
        if (is_array($input)) {
            foreach ($input as $col) {
                $this->addColumn($col);
            }
            return $this;
        }

        // group('a') atau group('a, b')
        if (is_string($input)) {
            foreach (explode(',', $input) as $col) {
                $this->addColumn($col);
            }
            return $this;
        }

        throw new InvalidArgumentException('Format group tidak dikenali.');
    }

    protected function addColumn($col): void
    {
        if (!is_string($col)) {
            throw new InvalidArgumentException('Kolom group harus string.');
        }

        $col = trim($col);
        if ($col === '') {
            return;   // lewati kosong
        }

        // hindari duplikat (opsional tapi berguna)
        if (!in_array($col, $this->groups, true)) {
            $this->groups[] = $col;
        }
    }

    public function hasGroups(): bool
    {
        return !empty($this->groups);
    }

    public function toArray(): array
    {
        return $this->groups;
    }


    public function toSql(): string
    {
        if (empty($this->groups)) {
            return '';
        }

        return 'GROUP BY ' . implode(', ', $this->groups);
    }

    public function __toString(): string
    {
        return $this->toSql();
    }
}