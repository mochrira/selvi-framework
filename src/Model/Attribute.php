<?php

namespace Selvi\Model;

use Override;
use Selvi\Model\Interfaces\IModelAttribute;
use Selvi\Model;

class Attribute implements IModelAttribute {

    /** @var class-string<Model> */
    private string $owner;

    /** @param class-string<Model> $owner */
    #[Override]
    public function setOwner(string $owner): void {
        $this->owner = $owner;
    }

    /** @return class-string<Model> */
    #[Override]
    public function getOwner(): string {
        return $this->owner;
    }
}
