<?php 

namespace Selvi\Database\Interfaces;

interface IBlueprint {

    public function getTable(): string;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getColumns(): array;
    public function hasIfNotExists(): bool;

}
