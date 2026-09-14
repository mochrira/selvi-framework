<?php 

namespace Selvi\Tests\Models;

use Selvi\Database\Attributes\BelongsTo;
use Selvi\Database\Attributes\Column;
use Selvi\Database\Attributes\Table;
use Selvi\Model;

#[Table('kontak', schema: 'main')]
class Kontak extends Model {
    
    #[Column('idKontak', key: true)]
    public int $idKontak;

    #[Column('nmKontak')]
    public ?string $nmKontak;

    #[Column('alamat')]
    public ?string $alamat;

    #[Column(name: 'idGrup')]
    public ?int $idGrup;

    #[BelongsTo(Grup::class, foreignKey: 'idGrup')]
    public ?Grup $grup = null;
}
