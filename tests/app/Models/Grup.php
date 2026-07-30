<?php 

namespace Selvi\Tests\Models;

use Selvi\Database\Attributes\Column;
use Selvi\Database\Attributes\Primary;
use Selvi\Database\Attributes\Schema;
use Selvi\Database\Attributes\Table;
use Selvi\Database\Model;

#[Schema("main")]
#[Table("grup")]
class Grup extends Model {

    #[Primary]
    #[Column("idGrup")]
    public ?int $idGrup = null;

    #[Column("nmGrup")]
    public ?string $nmGrup = null;

}