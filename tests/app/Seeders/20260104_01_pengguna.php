<?php

use Selvi\Database\Schema;

return function (Schema $schema) {

    $schema->table('pengguna')->insert([
        'nmPengguna' => 'Administrator',
        'username' => 'admin',
        'password' => md5('admin')
    ]);

};