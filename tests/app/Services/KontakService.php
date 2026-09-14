<?php 

namespace Selvi\Tests\Services;

use Selvi\Collection;

class KontakService {

    function result() {
        return Kontak::with('grup')
            ->with('address')
            ->limit(30)->offset(0);
            
        $result = Kontak::alias('k')
            ->leftJoinModel(Grup::class, foreign: 'idGrup', alias: 'g')
            ->get()->result();
        return Collection::fromMap(
            fn (mixed $item) => Kontak::of($item, 
                function (Kontak $kontak, mixed $item) {
                    $kontak->grup = Grup::of($item, 'g');
                }
            ), $result
        );
    }

}