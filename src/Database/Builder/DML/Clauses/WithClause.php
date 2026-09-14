<?php

declare(strict_types=1);

namespace Selvi\Database\Builder\DML\Clauses;

use InvalidArgumentException;
use Selvi\Database\Builder\DML\WithBuilder;

/**
 * Value object untuk SATU relasi pada `with()`.
 *
 * Isinya hanya nama relasi dan (opsional) relasi turunannya. Sama seperti
 * WhereClause, class ini tidak tahu rendering SQL maupun path/alias — path
 * (`grup`, `grup__wilayah`) baru dihitung saat traversal.
 *
 * Struktur kanonik yang dihasilkan toArray():
 *
 *     ['relation' => 'grup', 'with' => [
 *         ['relation' => 'wilayah', 'with' => []],
 *     ]]
 */
final class WithClause {

    private function __construct(
        private readonly string $relation,
        private readonly ?WithBuilder $nest = null
    ) { }

    public static function relation(string $relation, ?WithBuilder $nest = null): self
    {
        $relation = trim($relation);

        if ($relation === '') {
            throw new InvalidArgumentException('Nama relasi with tidak boleh kosong.');
        }

        return new self($relation, $nest);
    }

    public function toArray(): array
    {
        return [
            'relation' => $this->relation,
            'with'     => $this->nest?->toArray() ?? [],
        ];
    }
}
