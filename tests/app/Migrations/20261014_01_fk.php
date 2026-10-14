<?php

use Selvi\Database\Builder\DDL\AlterBlueprint;
use Selvi\Database\Schema;

return function (Schema $schema, string $direction) {

    if($direction == 'up') :
        $schema->alter('kontak', function (AlterBlueprint $table) {
            $table->foreign(['idGrup'], 'grup', ['id_grup'], 'fk_grup_kontak', 'RESTRICT', 'RESTRICT');
        });
    endif;

    if($direction == 'down') :
        $schema->alter('kontak', function (AlterBlueprint $table) {
            $table->dropForeign('fk_grup_kontak');
        });
    endif;

};