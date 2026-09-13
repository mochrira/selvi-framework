<?php

use Selvi\Database\Contracts\SchemaInterface;

return function (SchemaInterface $schema) {

    $schema->insert('pengguna', [
        'nmPengguna' => 'Administrator',
        'username' => 'admin',
        'password' => md5('admin')
    ]);

};