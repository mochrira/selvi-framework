<?php 


return function ($schema, $direction) {

  if($direction == 'up') :
      $schema->create('kontak', [
        'idKontak' => 'INT(11) PRIMARY KEY AUTO_INCREMENT',
        'nmKontak' => 'VARCHAR(150)',
        'alamat' => 'VARCHAR(150)',
        'kota' => 'VARCHAR(150)',
        'nomor' => 'VARCHAR(50)'
      ]);
  endif;

  if($direction == 'down') :
    $schema->drop('kontak');
  endif;

};