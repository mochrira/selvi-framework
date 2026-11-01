<?php

declare(strict_types=1);

namespace Selvi\Model;

use Override;
use Selvi\Data\DataProperty;
use Selvi\Model\Interfaces\IModelPropertyAttribute;
use Selvi\Model;

class PropertyAttribute extends DataProperty implements IModelPropertyAttribute {

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
