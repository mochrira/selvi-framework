<?php 

use Selvi\Database\Schema;

return function (Schema $schema, $direction) {

    if($direction == 'up') :
        $schema->create('pengguna', [
            'idPengguna' => 'INT(11) AUTO_INCREMENT PRIMARY KEY',
            'nmPengguna' => 'VARCHAR(150)',
            'username' => 'VARCHAR(50)',
            'password' => 'VARCHAR(150)',
            'akses' => 'VARCHAR(50)'
        ]);
    endif;

    if($direction == 'down') :
        $schema->drop('pengguna');
    endif;

};