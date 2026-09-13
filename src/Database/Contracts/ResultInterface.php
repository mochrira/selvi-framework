<?php 

namespace Selvi\Database\Contracts;

use stdClass;

interface ResultInterface {

    function __construct(mixed $result);
    function num_rows(): int | bool;
    function result(): array | bool | null;
    function row(): stdClass | bool | null;

}