<?php 

namespace Selvi\Model\Interfaces;

use Selvi\Model;

interface IAttribute {

    public function setOwner(string $owner): void;

    /** @var class-string<Model> */
    public function getOwner() : string;

}