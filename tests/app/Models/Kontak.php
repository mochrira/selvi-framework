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

    #[Column('id_departemen', nullable: true)]
    public ?int $idDepartemen = null;

    #[Column('id_unit', nullable: true)]
    public ?int $idUnit = null;

    #[Column('id_profesi', nullable: true)]
    public ?int $idProfesi = null;

    #[Column('created_at')]
    public ?DateTime $createdAt = null;
    
}
