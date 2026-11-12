<?php

declare(strict_types=1);

use Selvi\Database\Builder\DDL\Blueprint;
use Selvi\Database\Schema;

return function (Schema $schema, string $direction) {

    if($direction === 'up') {
        $schema->create('grup', function (Blueprint $table) {
            $table->integer('id_grup')->key()->autoIncrement();
            $table->string('nm_grup', 50)->nullable();
            $table->integer('parent_id')->nullable();
        });
    }

    if($direction === 'down') {
        $schema->drop('grup');
    }

};
