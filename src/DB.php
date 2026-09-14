<?php 

namespace Selvi;

use Selvi\Database\Builder\QueryBuilder;

class DB {
    public static function connection(string $name = '') {
        $builder = new QueryBuilder();
        $builder->useConnection($name);
        return $builder;
    }

    public static function table(string $table) {
        $builder = new QueryBuilder();
        $builder->table($table);
        return $builder;
    }
}