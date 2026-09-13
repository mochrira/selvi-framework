<?php

use Selvi\Database\Contracts\SchemaInterface;

return function (SchemaInterface $schema, string $direction) {

    if($direction == 'up') :
        $schema->rename('item', 'produk');
    endif;

    if($direction == 'down') :
        $schema->rename('produk', 'item');
    endif;

};