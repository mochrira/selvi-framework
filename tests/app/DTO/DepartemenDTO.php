<?php 

namespace Selvi\Tests\DTO;

use Selvi\Data\DataObject;
use Selvi\Data\DataProperty;

class DepartemenDTO extends DataObject {

    #[DataProperty('id_departemen')]
    public int $idDepartemen;

    #[DataProperty('nm_departemen')]
    public ?string $nmDepartemen;

}