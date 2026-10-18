<?php 

namespace Selvi\Database\Interfaces;

use Closure;

interface ISchemaBuilder {

    /**
     * @param ?Closure(IBlueprint): void $definition
     * @return bool true bila statement berhasil dieksekusi.
     */
    public function create(string $table, ?Closure $definition = null): bool;

    /**
     * @return bool true bila statement berhasil dieksekusi.
     */
    public function drop(string $table, bool $ifExists = true): bool;

    /**
     * @return bool true bila statement berhasil dieksekusi.
     */
    public function rename(string $table, string $newTable): bool;

    /**
     * @return bool true bila statement berhasil dieksekusi.
     */
    public function truncate(string $table): bool;

    /**
     * @param Closure(IAlterBlueprint): void $definition
     * @return bool true bila seluruh statement berhasil dieksekusi.
     */
    public function alter(string $table, Closure $definition): bool;

}
