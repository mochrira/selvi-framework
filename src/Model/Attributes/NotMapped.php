<?php

namespace Selvi\Model\Attributes;

use Attribute;
use Selvi\Model\Base\PropertyAttribute;

#[\Attribute(Attribute::TARGET_PROPERTY)]
class NotMapped extends PropertyAttribute {

    #[\Override]
    public function setPropertyName(string $name): void {
        parent::setPropertyName($name);
        $this->name = $name;
    }
}
