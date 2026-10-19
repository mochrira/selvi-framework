<?php

namespace Selvi\Model;

use Selvi\Model\Attributes\Column;
use Selvi\Model\Attributes\NotMapped;
use Selvi\Model\Interfaces\IAggregateAttribute;
use Selvi\Model\Interfaces\IPropertyAttribute;
use Selvi\Model\Interfaces\IRelationAttribute;

class RawProperty {

    private string $name;
    private string $type;

    /**
     * @var array<IPropertyAttribute>
     */
    private array $attributes;

    function __construct(
        string $name,
        string $type,
        array $attributes
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->attributes = $attributes;
    }

    public function name(): string {
        return $this->name;
    }

    public function type(): string {
        return $this->type;
    }

    /**
     * @return array<IPropertyAttribute>
     */
    public function attributes(): array {
        return $this->attributes;
    }

    function column(): ?Column {
        $result = array_filter($this->attributes, fn($obj) => $obj instanceof Column);
        if (empty($result)) return null;
        return $result[0];
    }

    function relation(): ?IRelationAttribute {
        $result = array_filter($this->attributes, fn($obj) => $obj instanceof IRelationAttribute);
        if (empty($result)) return null;
        return $result[0];
    }

    function aggregate(): ?IAggregateAttribute {
        $result = array_filter($this->attributes, fn($obj) => $obj instanceof IAggregateAttribute);
        if (empty($result)) return null;
        return $result[0];
    }

    function notMapped(): ?NotMapped {
        $result = array_filter($this->attributes, fn($obj) => $obj instanceof NotMapped);
        if (empty($result)) return null;
        return $result[0];
    }
}
