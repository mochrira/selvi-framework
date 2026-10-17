<?php 

namespace Selvi\Tests\Models;

use DateTime;
use Selvi\Model\Attributes\BelongsTo;
use Selvi\Model\Attributes\Column;
use Selvi\Model\Attributes\Table;
use Selvi\Model;

#[Table('kontak', schema: 'main')]
class Kontak extends Model {

    #[Column('id_kontak', key: true)]
    public int $idKontak;

    #[Column('nm_kontak')]
    public ?string $nmKontak = null;

    #[Column('id_grup', nullable: true)]
    public ?int $idGrup = null;

    #[BelongsTo(Grup::class, 'id_grup', includeIfNull: true)]
    public ?Grup $grup = null;

    #[Column('created_at')]
    public ?DateTime $createdAt = null;
    
}
