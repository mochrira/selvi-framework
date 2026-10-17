<?php

use Selvi\Database\Builder\DDL\AlterBlueprint;
use Selvi\Database\Builder\DDL\Blueprint;
use Selvi\Database\Schema;

return function (Schema $schema, string $direction) {

    if($direction == 'up') :
        $schema->rename('grup', 'departemen');
        $schema->alter('departemen', function (AlterBlueprint $table) {
            $table->renameColumn('id_grup', 'id_departemen');
            $table->renameColumn('nm_grup', 'nm_departemen');
        });

        $schema->create('unit', function (Blueprint $table) {
            $table->integer('id_unit')->autoIncrement()->key();
            $table->string('nm_unit')->nullable();
            $table->integer('id_departemen')->nullable();
        });
        $schema->alter('unit', function (AlterBlueprint $table) {
            $table->foreign(['id_departemen'], 'departemen', ['id_departemen'], 'fk_departemen_unit');
        });

        $schema->create('profesi', function (Blueprint $table) {
            $table->integer('id_profesi')->autoIncrement()->key();
            $table->string('nm_profesi')->nullable();
            $table->integer('id_unit')->nullable();            
        });
        $schema->alter('profesi', function (AlterBlueprint $table) {
            $table->foreign(['id_unit'], 'unit', ['id_unit'], 'fk_unit_profesi');
        });

        $schema->alter('kontak', function (AlterBlueprint $table) {
            $table->renameColumn('id_grup', 'id_departemen');
            $table->integer('id_unit')->after('id_departemen')->nullable();
            $table->integer('id_profesi')->after('id_unit')->nullable();
        });
        $schema->alter('kontak', function (AlterBlueprint $table) {
            $table->foreign(['id_unit'], 'unit', ['id_unit'], 'fk_unit_kontak');
            $table->foreign(['id_profesi'], 'profesi', ['id_profesi'], 'fk_profesi_kontak');
        });
    endif;

    if($direction == 'down') :
        $schema->alter('kontak', function (AlterBlueprint $table) {
            $table->dropForeign('fk_unit_kontak');
            $table->dropForeign('fk_profesi_kontak');
        });
        $schema->alter('kontak', function (AlterBlueprint $table) {
            $table->renameColumn('id_departemen', 'id_grup');
            $table->dropColumn('id_unit');
            $table->dropColumn('id_profesi');
        });

        $schema->drop('profesi');
        $schema->drop('unit');

        $schema->alter('departemen', function (AlterBlueprint $table) {
            $table->renameColumn('id_departemen', 'id_grup');
            $table->renameColumn('nm_departemen', 'nm_grup');
        });
        $schema->rename('departemen', 'grup');
    endif;

};