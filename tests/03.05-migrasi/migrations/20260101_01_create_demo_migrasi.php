<?php

use Selvi\Database\Builder\DDL\Blueprint;
use Selvi\Database\Schema;

return function (Schema $schema, string $direction) {

    if($direction === 'up') {
        $schema->create('demo_migrasi', function (Blueprint $table) {
            $table->integer('id')->key()->autoIncrement();
            $table->string('nama', 50);
        });
    }

    if($direction === 'down') {
        $schema->drop('demo_migrasi');
    }

};
