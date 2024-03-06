<?php 

namespace Selvi\Database;

interface Schema {

    public function __construct(Array $config);
    public function connect(): bool;
    public function disconnect(): bool;
    public function select_db(string $db): bool;
    public function query(string $sql): Result | bool;
    public function getSql(string $tbl): string;
    public function get(string $tbl): Result;
    public function select(string|array $cols): self;
    public function where(string|array $where): self;
    public function order(string|array $cols, ?string $param = "ASC"): self;
    public function limit (int $limit): self;
    public function offset(int $offset): self;
    public function insert(string $tbl, array $data): Result | bool;
    public function create(string $table, array $columns): Result | bool;
    public function drop(string $table): Result | bool;
    public function prepareMigrationTables(): Result | bool;
    public function error(): mixed;
    public function update(string $tbl, array $data): Result | bool;
    public function delete(string $tbl): Result | bool; 
    public function join(string $tbl, string $cond): self;
    public function innerJoin(string $tbl, string $cond): self;
    public function leftJoin(string $tbl, string $cond): self;
    
    public function groupBy(mixed $group): self;
    // public function orWhere(mixed $param, mixed $param2): self;
    public function startTransaction(): bool;
    public function rollback(): bool;
    public function commit(): bool;

    // public function modifyColumn(string $column, string $type): void;
    // public function changeColumn(string $table,  string $column, string $new_column, string $type): void;
    // public function addColumn(string $column, string $type): void;
    // public function addColumnFirst(string $column, string $type): void;
    // public function addColumnAfter(string $afterCol, string $column, string $type): void;
    // public function dropColumn(string $column): void;
    // public function dropPrimary(): void;
    // public function alter(string $table): string;
    // public function rename(string $table,string $new_table): string;

    // public function createSchema(string $name): string;
    // public function dropSchema(string $name): string;
    // public function createIndex(string $table, string $index_name, array $cols): string;
    // public function createLike(string $table,string $new_table): string;
    // public function copyData(string $table,string $new_table): string;
    // public function truncate( string $table): string;
    // public function dropIndex(string $table, string $index_name): string;
    // public function addPrimary(string $string): void;
    // public function is_json(string $json): bool;
    
}