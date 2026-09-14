<?php 

namespace Selvi\Tests\Models;

use Selvi\Database\Attributes\Column;
use Selvi\Database\Attributes\Table;
use Selvi\Model;

#[Table('grup', 'main')]
class Grup extends Model {

    #[Column('idGrup', key: true)]
    public int $idGrup;

    #[Column('nmGrup')]
    public ?string $nmGrup;

}