<?php 

namespace Selvi\Tests\Models;

use Selvi\Model;
use Selvi\Model\Attributes\BelongsTo;
use Selvi\Model\Attributes\Column;
use Selvi\Model\Attributes\Table;

#[Table('unit', 'main')]
class Unit extends Model {

    #[Column('id_unit', key: true)]
    public int $idUnit;

    #[Column('nm_unit')]
    public ?string $nmUnit;

    #[Column('id_departemen', nullable: true)]
    public ?int $idDepartemen = null;

    #[BelongsTo(Departemen::class, 'id_departemen', includeIfNull: true)]
    public ?Departemen $departemen = null;

}