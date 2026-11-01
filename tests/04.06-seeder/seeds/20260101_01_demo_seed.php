<?php

use Selvi\Database\Schema;

return function (Schema $schema) {

    $schema->table('demo_seed')->insert([
        ['nama' => 'Seed A', 'nilai' => 1],
        ['nama' => 'Seed B', 'nilai' => 2],
    ]);

};
