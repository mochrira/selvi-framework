<?php 

namespace Selvi\Tests\Models;

use App\Models\Grup;
use Selvi\Database\Attributes\Column;
use Selvi\Database\Attributes\Table;
use Selvi\Model;

#[Table('kontak', schema: 'main')]
class Kontak extends Model {
    
    #[Column('idKontak', key: true)]
    public int $idKontak;

    #[Column('nmKontak')]
    public ?string $nmKontak;

    #[Column(name: 'idGrup')]
    public ?int $idGrup;

    public ?Grup $grup;

}
