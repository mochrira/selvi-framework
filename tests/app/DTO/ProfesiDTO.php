<?php 

namespace Selvi\Tests\DTO;

use Selvi\Data\DataObject;
use Selvi\Data\DataProperty;

class ProfesiDTO extends DataObject {

    #[DataProperty('id_profesi')]
    public int $idProfesi;

    #[DataProperty('nm_profesi')]
    public ?string $nmProfesi;

    #[DataProperty('id_unit')]
    public ?int $idUnit = null;

    #[DataProperty(prefix: 'unit')]
    public ?UnitDTO $unit;

}