<?php

namespace Selvi\Model\Base;

use Override;
use Selvi\Model;
use Selvi\Model\Interfaces\IAttribute;

class Attribute implements IAttribute {

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
