<?php 

namespace Selvi\Data;

use Attribute;
use Override;
use Selvi\Data\Interfaces\IDataProperty;
use Selvi\Database\Casting\Converter;
use Selvi\Database\Interfaces\ICast;
use Selvi\Database\Interfaces\ISerializesCast;

#[\Attribute(Attribute::TARGET_PROPERTY)]
class DataProperty implements IDataProperty {

    public string $name = "";
    public ?string $prefix = null;
    public ?bool $optional = false;
    public ?ICast $cast = null;
    public ?ISerializesCast $serialize = null;

    public function __construct(
        string $name = "",
        ?string $prefix = null,
        ?bool $optional = false,
        ?ICast $cast = null,
        ?ISerializesCast $serialize = null
    ) {
        $this->name = $name;
        $this->prefix = $prefix;
        $this->optional = $optional;
        $this->cast = $cast;
        $this->serialize = $serialize;
    }

    private string $propertyName;
    private string $propertyType;
    private bool $allowsNull;

    #[Override]
    public function setPropertyName(string $name): void {
        $this->propertyName = $name;
    }

    #[Override]
    public function setPropertyType(string $type, bool $allowsNull): void {
        $this->propertyType = $type;
        $this->allowsNull = $allowsNull;
    }

    #[Override]
    public function propertyName(): string
    {
        return $this->propertyName;
    }

    #[Override]
    public function propertyType(): string
    {
        return $this->propertyType;
    }

    #[Override]
    public function allowsNull(): bool
    {
        return $this->allowsNull;
    }

    protected function dataPrefix(): ?string {
        return $this->prefix;
    }

    #[Override]
    public function assign(object $target, DataRow $row): void {
        if(!$row->has($this->name)) {
            $nestedPrefix = $this->dataPrefix();
            if(
                $nestedPrefix !== null
                && $nestedPrefix !== ""
                && is_subclass_of($this->propertyType(), DataObject::class)
            ) {
                $child = $row->child($nestedPrefix);
                if($child->hasAny($child->prefix())) {
                    DataObject::assignValue(
                        $target,
                        $this->propertyName(),
                        $this->propertyType()::of($child->item(), $child->prefix())
                    );
                    return;
                }
            }

            if($this->allowsNull()) {
                DataObject::assignValue($target, $this->propertyName(), null);
            }
            return;
        }

        $value = $row->get($this->name);

        if(is_subclass_of($this->propertyType(), DataObject::class)) {
            if($value !== null) {
                $value = $this->propertyType()::of($value);
            }
        } else {
            $value = Converter::instance()->get(
                type: $this->propertyType(),
                value: $value,
                cast: $this->cast
            );
        }

        DataObject::assignValue($target, $this->propertyName(), $value);
    }

}