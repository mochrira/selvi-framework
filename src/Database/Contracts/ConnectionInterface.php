<?php 

namespace Selvi\Database\Contracts;

use PDO;

interface ConnectionInterface {

    public function getConfig(): array;
    public function pdo(): PDO;
    public function connect(): bool;
    public function disconnect(): bool;
    public function isConnected(): bool;
    public function beginTransaction(): bool;
    public function commit(): bool;
    public function rollback(): bool;
    public function select(string $sql, array $params = []): array;
    public function insert(string $sql, array $params = []): int|string;
    public function update(string $sql, array $params = []): int;
    public function delete(string $sql, array $params = []): int;
    public function getGrammar(): GrammarInterface;
    public function setGrammar(GrammarInterface $grammar): void;

}