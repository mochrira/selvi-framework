<?php 

namespace Selvi\Tests\DTO;

use Selvi\Data\DataObject;
use Selvi\Data\DataProperty;

class UnitDTO extends DataObject {

    #[DataProperty('id_unit')]
    public int $idUnit;

    #[DataProperty('nm_unit')]
    public ?string $nmUnit;

    #[DataProperty('id_departemen')]
    public ?int $idDepartemen = null;

    #[DataProperty(prefix: 'departemen')]
    public ?DepartemenDTO $departemen;

}