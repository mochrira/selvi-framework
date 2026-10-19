<?php 

namespace Selvi\Database\Interfaces;

interface IAlterBlueprint {

    public function getTable(): string;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getOperations(): array;

}
