<?php 

namespace Selvi\Model\Contracts;

use Selvi\Model;

interface IAttribute {

    /** @param class-string<Model> $model */
    public function setOwner(string $model): void;

    /** @return class-string<Model> */
    public function owner(): string;

}