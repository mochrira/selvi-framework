<?php 

namespace App\Models;

use Selvi\Database\Attributes\Column;
use Selvi\Database\Attributes\Table;
use Selvi\Model;

#[Table('grup', 'main')]
class Grup extends Model {

    #[Column('idGrup')]
    public int $idGrup;

    #[Column('nmGrup')]
    public ?string $nmGrup;

}