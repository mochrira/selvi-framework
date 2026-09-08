<?php 

namespace Selvi\Tests\Models;

use Selvi\Database\Attributes\Column;
use Selvi\Database\Attributes\Primary;
use Selvi\Database\Attributes\Table;
use Selvi\Database\Attributes\Schema;
use Selvi\Database\Model;

#[Schema("main")]
#[Table("kontak")]
class Kontak extends Model {

    #[Primary]
    #[Column("idKontak")]
    public ?int $idKontak = null;

    #[Column("nmKontak")]
    public ?string $nmKontak = null;

    #[Column("idGrup")]
    public ?int $idGrup = null;

}