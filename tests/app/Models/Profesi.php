<?php 

namespace Selvi\Tests\Models;

use Selvi\Model;
use Selvi\Model\Attributes\BelongsTo;
use Selvi\Model\Attributes\Column;
use Selvi\Model\Attributes\Table;

#[Table('profesi', 'main')]
class Profesi extends Model {

    #[Column('id_profesi', key: true)]
    public int $idProfesi;

    #[Column('nm_profesi')]
    public ?string $nmProfesi;

    #[Column('id_unit')]
    public ?int $idUnit = null;

    #[BelongsTo(Unit::class, 'id_unit')]
    public ?Unit $unit = null;

}