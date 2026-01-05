<?php

use Selvi\Database\Schema;

return function (Schema $schema) {

    $schema->insert('pengguna', [
        'nmPengguna' => 'Administrator',
        'username' => 'admin',
        'password' => md5('admin')
    ]);

};