<?php

use Selvi\Database\Builder\DDL\Blueprint;
use Selvi\Database\Schema;

return function (Schema $schema, string $direction) {

    if($direction === 'up') {
        $schema->create('produk', function (Blueprint $table) {
            $table->integer('id_produk')->key()->autoIncrement();
            $table->string('nama', 100);
            $table->integer('harga');
            $table->integer('stok')->default(0);
            $table->datetime('created_at');
            $table->datetime('updated_at');
        });
    }

    if($direction === 'down') {
        $schema->drop('produk');
    }

};
