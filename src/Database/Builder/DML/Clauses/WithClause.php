<?php

declare(strict_types=1);

namespace Selvi\Database\Builder\DML\Clauses;

use InvalidArgumentException;
use Selvi\Database\Builder\DML\WithBuilder;
use Selvi\Model\Contracts\IRelationAttribute;

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
class WithClause {

    public function __construct(
        private readonly IRelationAttribute $relation
    ) { }

    function build() {
    
    }

}
