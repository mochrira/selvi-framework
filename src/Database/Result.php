<?php 

namespace Selvi\Database;

use stdClass;

interface Result {

    function __construct(mixed $result);
    function num_rows(): int;
    function result(): array | bool | null;
    function row(): stdClass | bool | null;

}