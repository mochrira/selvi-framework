<?php 

namespace Selvi\Data\Interfaces;

interface IDataObject {

    public static function of(object $obj, ?string $prefix = ""): static;

}