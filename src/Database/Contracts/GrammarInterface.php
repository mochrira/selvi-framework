<?php 

namespace Selvi\Database\Contracts;

use Selvi\Database\QueryBuilder;

interface GrammarInterface {

    /**
     * Susun query SELECT dari state QueryBuilder.
     *
     * @return array{0: string, 1: array} [SQL, parameter]
     */
    public function compileSelect(QueryBuilder $queryBuilder): array;

    /**
     * Susun query INSERT.
     *
     * @return array{0: string, 1: array} [SQL, parameter]
     */
    public function compileInsert(QueryBuilder $queryBuilder, array $data): array;

    /**
     * Susun query UPDATE (menggunakan WHERE dari QueryBuilder).
     *
     * @return array{0: string, 1: array} [SQL, parameter]
     */
    public function compileUpdate(QueryBuilder $queryBuilder, array $data): array;

    /**
     * Susun query DELETE (menggunakan WHERE dari QueryBuilder).
     *
     * @return array{0: string, 1: array} [SQL, parameter]
     */
    public function compileDelete(QueryBuilder $queryBuilder): array;

}
