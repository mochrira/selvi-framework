<?php

use Selvi\Database\Schema;

return function(Schema $schema, $direction) {

    if($direction == 'up') :
        $schema->create('kontak', [
            'idKontak' => 'INT IDENTITY(1,1) PRIMARY KEY',
            'nmKontak' => 'VARCHAR(150)',
            'idGrup' => 'INT'
        ]);

        $schema->create('grup', [
            'idGrup' => 'INT IDENTITY(1,1) PRIMARY KEY',
            'nmGrup' => 'VARCHAR(50)'
        ]);
    endif;

    if($direction == 'down') :
        $schema->drop('grup');
        $schema->drop('kontak');
    endif;

};