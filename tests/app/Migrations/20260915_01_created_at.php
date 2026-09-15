<?php

use Selvi\Database\Builder\DDL\AlterBlueprint;
use Selvi\Database\Schema;

return function (Schema $schema, string $direction) {

    if($direction == 'up') :
        $schema->alter('kontak', function (AlterBlueprint $table) {
            $table->datetime('createdAt');
        });
    endif;

    if($direction == 'down') :
        $schema->alter('kontak', function (AlterBlueprint $table) {
            $table->dropColumn('createdAt');
        });
    endif;

};