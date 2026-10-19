<?php

use Selvi\Database\Builder\DDL\Blueprint;
use Selvi\Database\Schema;

return function (Schema $schema, string $direction) {

    if($direction == 'up') :
        $schema->create('transaksi', function (Blueprint $table) {
            $table->integer('id_transaksi')->autoIncrement()->key();
            $table->datetime('tanggal')->nullable();
            $table->integer('id_kontak')->nullable();
            $table->decimal('sales', 10, 2)->nullable();
        });
    endif;

    if($direction == 'down') :
        $schema->drop('transaksi');
    endif;

};