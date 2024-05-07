<?php

use Selvi\Database\Schema;

return function(Schema $schema, $direction) {

    if($direction == 'up') :
        $schema->connect();
        $schema->create('kontak', [
            'idKontak' => 'INT(11) PRIMARY KEY AUTO_INCREMENT',
            'nmKontak' => 'VARCHAR(150)',
            'idGrup' => 'INT(11)'
        ]);

        $schema->create('grup', [
            'idGrup' => 'INT(11) PRIMARY KEY AUTO_INCREMENT',
            'nmGrup' => 'VARCHAR(50)'
        ]);
    endif;

    if($direction == 'down') :
        $schema->connect();
        $schema->drop('grup');
        $schema->drop('kontak');
    endif;

};