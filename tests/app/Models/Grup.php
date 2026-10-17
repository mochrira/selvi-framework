<?php 

namespace Selvi\Tests\Models;

use Selvi\Model;
use Selvi\Model\Attributes\Column;
use Selvi\Model\Attributes\NotMapped;
use Selvi\Model\Attributes\Table;

#[Table('grup', 'main')]
class Grup extends Model {

    #[Column('id_grup', key: true)]
    public int $idGrup;

    #[Column('nm_grup')]
    public ?string $nmGrup;

    #[NotMapped(includeIfNull: false)]
    public ?string $randomCode = null;

}