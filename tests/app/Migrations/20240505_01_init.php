<?php

use Selvi\Database\Builder\DDL\Blueprint;
use Selvi\Database\Schema;

return function (Schema $schema, string $direction) {

    if($direction === 'up') {
        $schema->create('kontak', function (Blueprint $table) {
            $table->integer('id_kontak')->key()->autoIncrement();
            $table->string('nmKontak', 150)->nullable();
            $table->integer('idGrup')->nullable();
        });

        $schema->create('grup', function (Blueprint $table) {
            $table->integer('id_grup')->key()->autoIncrement();
            $table->string('nmGrup', 50)->nullable();
        });
    }

    if($direction === 'down') {
        $schema->drop('grup');
        $schema->drop('kontak');
    }

};
