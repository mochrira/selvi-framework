<?php

use Selvi\Database\Schema;

return function(Schema $schema, $direction) {

    if($direction == 'up') :
        $schema->create('transaksi', [
            'idTransaksi' => 'INT(11) PRIMARY KEY AUTO_INCREMENT',
            'idKontak' => 'INT(11)',
            'tanggal' => 'DATE',
            'total' => 'INT(11)'
        ]);

        $schema->create('produk', [
            'idProduk' => 'INT(11) PRIMARY KEY AUTO_INCREMENT',
            'nmProduk' => 'VARCHAR(50)'
        ]);

        $schema->create('transaksiDetail', [
            'idTransaksi' => 'INT(11)',
            'idProduk' => 'INT(11)',
            'harga' => 'DECIMAL(13,2)',
            'jumlah' => 'INT(11)',
            'total' => 'INT(11)'
        ]);
    endif;

    if($direction == 'down') :
        $schema->drop('transaksiDetail');
        $schema->drop('produk');
        $schema->drop('transaksi');
    endif;

};