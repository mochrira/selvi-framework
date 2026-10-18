<?php 

namespace Selvi\Database\Interfaces;

interface IConnection {

    // Evaluated
    public function getConfig(): array | null;
    public function connect(): bool;
    public function disconnect(): bool;

    public function grammar(): IGrammar;
    public function query(string $sql): IResult | bool;
    
    public function lastId(): int;
    // End of Evaluated

    public function startTransaction(): bool;
    public function commit(): bool;
    public function rollback(): bool;

}