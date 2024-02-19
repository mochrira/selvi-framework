<?php 

return function ($schema, $direction) {

    if($direction == 'up') :
        $schema->create('kontak', [
            'idKontak' => 'INT(11) AUTO_INCREMENT PRIMARY KEY',
            'nmKontak' => 'VARCHAR(150)'
        ]);
    endif;

    if($direction == 'down') :
        $schema->drop('kontak');
    endif;

};