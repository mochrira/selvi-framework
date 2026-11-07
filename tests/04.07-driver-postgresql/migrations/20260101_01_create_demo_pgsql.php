<?php

use Selvi\Database\Builder\DDL\Blueprint;
use Selvi\Database\Schema;

return function (Schema $schema, string $direction) {

    if($direction === 'up') {
        $schema->create('demo_pgsql_migrasi', function (Blueprint $table) {
            $table->integer('id')->key()->autoIncrement();
            $table->string('nama', 100);
            $table->boolean('aktif')->default(true);
            $table->datetime('dibuat')->nullable();
        });
    }

    if($direction === 'down') {
        $schema->drop('demo_pgsql_migrasi');
    }

};
