<?php 

namespace Selvi\Database;

use Selvi\Database\QueryResult;

interface Schema {

    public function __construct(Array $config);
    public function connect(): bool;
    public function disconnect(): bool;
    public function select_db(string $db): bool;
    public function query(string $sql): QueryResult;

}