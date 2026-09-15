<?php 

namespace Selvi\Tests\Models;

use DateTime;
use Selvi\Database\Attributes\BelongsTo;
use Selvi\Database\Attributes\Column;
use Selvi\Database\Attributes\Table;
use Selvi\Model;

#[Table('kontak', schema: 'main')]
class Kontak extends Model {
    
    #[Column('id_kontak', key: true)]
    public int $idKontak;

    #[Column('nmKontak')]
    public ?string $nmKontak;

    #[Column(name: 'idGrup')]
    public ?int $idGrup;

    #[Column(name: 'createdAt')]
    public ?DateTime $createdAt;

    #[BelongsTo(Grup::class, foreignKey: 'idGrup')]
    public ?Grup $grup = null;
}
