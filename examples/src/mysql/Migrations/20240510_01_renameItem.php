<?php

use Selvi\Database\Schema;

return function (Schema $schema, string $direction) {

    if($direction == 'up') :
        $schema->rename('item', 'produk');
    endif;

    if($direction == 'down') :
        $schema->rename('produk', 'item');
    endif;

};