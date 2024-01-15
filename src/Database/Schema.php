<?php 

namespace Selvi\Database;

interface Schema {

    public function __construct(Array $config);
    public function connect(): bool;
    public function disconnect(): bool;
    public function select_db(string $db): bool;
    public function query(string $sql): Result;

    public function getSql(string $tbl): string;
    public function get(string $tbl): Result;

    public function select(string | array  $cols): self;
    public function where(string $where): self;

    // public function limit (int $limit): void;
    // public function offset(): void;
    // public function innerJoin(string $tbl, string $cond): self;
    // public function leftJoin(string $tbl, string $cond): self;
    // public function join(string $tbl, string $cond): self;
    // public function where(mixed $param, mixed $param2): self;
    // public function orWhere(mixed $param, mixed $param2): self;
    // public function groupBy(mixed $group): self;
    // public function is_json(string $json): bool;
    // public function prepareValue(mixed $val): mixed;
    // public function insert(string $tbl, array $data): string;
    // public function update(string $tbl, array $data): string;
    // public function delete(string $tbl): string;
    
    // public function createDb(string $name): string;
    // public function create(string $name, array $column, array $props): string;
    // public function createSchema(string $name): string;
    // public function dropSchema(string $name): string;
    // public function createIndex(string $table, string $index_name, array $cols): string;
    // public function rename(string $table,string $new_table): string;
    // public function createLike(string $table,string $new_table): string;
    // public function copyData(string $table,string $new_table): string;
    // public function truncate( string $table): string;
    // public function drop(string $table): string;
    // public function dropIndex(string $table, string $index_name): string;
    // public function modifyColumn(string $column, string $type): void;
    // public function changeColumn(string $table,  string $column, string $new_column, string $type): void;
    // public function addColumn(string $column, string $type): void;
    // public function addColumnFirst(string $column, string $type): void;
    // public function addColumnAfter(string $afterCol, string $column, string $type): void;
    // public function dropColumn(string $column): void;
    // public function dropPrimary(): void;
    // public function addPrimary(string $string): void;
    // public function startTransaction(): string;
    // public function rollback(): string;
    // public function commit(): string;
    // public function alter(string $table): string;
    // public function order(mixed $param, mixed $param2): void;
    
}