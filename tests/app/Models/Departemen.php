<?php 

namespace Selvi\Tests\Models;

use Selvi\Model;
use Selvi\Model\Attributes\Column;
use Selvi\Model\Attributes\Table;

#[Table('departemen', 'main')]
class Departemen extends Model {

    #[Column('id_departemen', key: true)]
    public int $idDepartemen;

    #[Column('nm_departemen')]
    public ?string $nmDepartemen;

}