<?php

namespace Selvi\Model\Attributes;

use Selvi\Model;
use Selvi\Model\Contracts\IAttribute;

#[\Attribute]
class Table implements IAttribute {

    /** @var class-string<Model> */
    private string $owner;

    public function __construct(
        public string $name,
        public string $schema
    ) { }

    #[\Override]
    public function setOwner(string $owner): void {
        $this->owner = $owner;
    }

    #[\Override]
    public function owner(): string {
        return $this->owner;
    }
}
