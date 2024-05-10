<?php

use Selvi\Database\Schema;

return function(Schema $schema, $direction) {

    if($direction == 'up') :
        $schema->create('transaksi', [
            'idTransaksi' => 'INT IDENTITY(1,1) PRIMARY KEY',
            'idKontak' => 'INT',
            'tanggal' => 'DATE',
            'total' => 'INT'
        ]);

        $schema->create('item', [
            'idProduk' => 'INT IDENTITY(1,1) PRIMARY KEY',
            'nmProduk' => 'VARCHAR(50)',
            'harga' => 'INT'
        ]);

        $schema->create('transaksiDetail', [
            'id' => 'INT IDENTITY(1,1) PRIMARY KEY',
            'idTransaksi' => 'INT',
            'idProduk' => 'INT',
            'harga' => 'DECIMAL(13,2)',
            'jumlah' => 'INT',
            'total' => 'INT'
        ]);
    endif;

    if($direction == 'down') :
        $schema->drop('transaksiDetail');
        $schema->drop('item');
        $schema->drop('transaksi');
    endif;

};