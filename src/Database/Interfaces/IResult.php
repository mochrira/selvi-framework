<?php 

namespace Selvi\Database\Interfaces;

use stdClass;

interface IResult {

    function __construct(mixed $result);
    function num_rows(): int | bool;
    /**
     * @deprecated since 3.0.1, use rows() instead
     */
    function result(): array | bool | null;
    function rows(): array | bool | null;
    function row(): stdClass | bool | null;

}