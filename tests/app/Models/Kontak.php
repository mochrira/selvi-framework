<?php 

namespace Selvi\Tests\Models;

use DateTime;
use Selvi\Model;
use Selvi\Model\Attributes\BelongsTo;
use Selvi\Model\Attributes\Column;
use Selvi\Model\Attributes\Table;

#[Table('kontak', schema: 'main')]
class Kontak extends Model {
    
    #[Column('id_kontak', key: true)]
    public int $idKontak;

    #[Column('nm_kontak')]
    public ?string $nmKontak;

    #[Column('id_departemen')]
    public ?int $idDepartemen;

    #[Column('id_unit')]
    public ?int $idUnit;

    #[Column('id_profesi')]
    public ?int $idProfesi;

    #[Column('created_at')]
    public ?DateTime $createdAt;

    #[BelongsTo(Departemen::class, foreignKey: 'id_departemen')]
    public ?Departemen $departemen;

    #[BelongsTo(Unit::class, foreignKey: 'id_unit')]
    public ?Unit $unit;

    #[BelongsTo(Profesi::class, foreignKey: 'id_profesi')]
    public ?Profesi $profesi;

}
