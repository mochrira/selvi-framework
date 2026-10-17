<?php

use Selvi\Database\Builder\DDL\AlterBlueprint;
use Selvi\Database\Schema;

return function(Schema $schema, string $direction) {

    if($direction == 'up') :
        $schema->alter('kontak', function (AlterBlueprint $table) {
            $table->renameColumn('nmKontak', 'nm_kontak');
            $table->renameColumn('idGrup', 'id_grup');
            $table->renameColumn('createdAt', 'created_at');
        });

        $schema->alter('grup', function (AlterBlueprint $table) {
            $table->renameColumn('nmGrup', 'nm_grup');
        });
    endif;

    if($direction == 'down') :
        $schema->alter('kontak', function (AlterBlueprint $table) {
            $table->renameColumn('nm_kontak', 'nmKontak');
            $table->renameColumn('id_grup', 'idGrup');
            $table->renameColumn('created_at', 'createdAt');
        });

        $schema->alter('grup', function (AlterBlueprint $table) {
            $table->renameColumn('nm_grup', 'nmGrup');
        });
    endif;

};