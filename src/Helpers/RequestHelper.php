<?php

declare(strict_types=1);

if(!function_exists('parseSort')) {
    function parseSort(string $sort): array {
        $orders = [];

        foreach (explode(',', $sort) as $part) {
            $part = trim($part);
            if ($part === '') continue;

            if (substr_count($part, ':') !== 1) {
                throw new InvalidArgumentException("Format sort tidak valid: '{$part}'. Gunakan 'kolom:ASC' atau 'kolom:DESC'.");
            }

            [$column, $direction] = explode(':', $part, 2);
            $column = trim($column);
            $direction = strtoupper(trim($direction));

            if ($column === '') {
                throw new InvalidArgumentException("Nama kolom pada sort tidak boleh kosong: '{$part}'.");
            }

            if ($direction !== 'ASC' && $direction !== 'DESC') {
                throw new InvalidArgumentException("Arah sort harus 'ASC' atau 'DESC', diberikan: '{$direction}'.");
            }

            $orders[$column] = $direction;
        }

        return $orders;
    }

}