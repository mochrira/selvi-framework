<?php 

namespace Selvi\Tests\DTO;

use Selvi\Data\DataObject;
use Selvi\Data\DataProperty;

class KontakDTO extends DataObject {

    #[DataProperty(name: 'id_kontak')]
    public int $idKontak;

    #[DataProperty(name: 'nm_kontak')]
    public ?string $nmKontak;

    #[DataProperty(name: 'id_departemen')]
    public ?int $idDepartemen;

    #[DataProperty(prefix: 'departemen')]
    public ?DepartemenDTO $departemen;

    #[DataProperty(prefix: 'unit')]
    public ?UnitDTO $unit;

    #[DataProperty(prefix: 'profesi')]
    public ?ProfesiDTO $profesi;

}