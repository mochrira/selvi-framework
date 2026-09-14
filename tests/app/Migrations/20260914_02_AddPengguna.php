<?php

use Selvi\Database\Builder\DDL\Blueprint;
use Selvi\Database\Schema;

return function (Schema $schema, string $direction) {

    if($direction === 'up') {
        $schema->create('pengguna', function (Blueprint $table) {
            $table->integer('idPengguna')->key()->autoIncrement();
            $table->string('nama', 150)->nullable();
            $table->string('email', 150)->nullable()->unique();
            $table->string('password', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });

    }

    if($direction === 'down') {
        $schema->drop('pengguna');
    }

};
