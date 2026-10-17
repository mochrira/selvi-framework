<?php 

namespace Selvi\Database\Contracts;

use stdClass;

interface ResultInterface {

    function __construct(mixed $result);
    function num_rows(): int | bool;
    /**
     * @deprecated since 3.0.1, use rows() instead
     */
    function result(): array | bool | null;
    function rows(): array | bool | null;
    function row(): stdClass | bool | null;

}