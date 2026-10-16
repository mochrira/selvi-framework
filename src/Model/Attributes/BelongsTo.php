<?php

namespace Selvi\Model\Attributes;

use Override;
use Selvi\Contracts\Arrayable;
use Selvi\Model;
use Selvi\Database\Builder\DML\QueryBuilder;
use Selvi\Model\Contracts\IRelationAttribute;

#[\Attribute]
class BelongsTo implements IRelationAttribute {

    /**
     * @param class-string<Model> $model
     */
    public function __construct(
        public string $model,
        public string $foreignKey,
        public ?string $ownerKey = null,
        public bool $includeIfNull = false
    ) { }

    /** @var class-string<Model> */
    private string $owner;
    private string $propertyName;
    private string $propertyType;

    #[\Override]
    public function setOwner(string $owner): void {
        $this->owner = $owner;
    }

    #[\Override]
    public function setPropertyName(string $name): void {
        $this->propertyName = $name;
    }

    #[\Override]
    public function setPropertyType(string $type): void {
        $this->propertyType = $type;
    }

    #[\Override]
    public function owner(): string {
        return $this->owner;
    }

    #[\Override]
    public function propertyName(): string {
        return $this->propertyName;
    }

    #[\Override]
    public function propertyType(): string {
        return $this->propertyType;
    }

    private string $columnPrefix = "";

    /**
     * @param array<string> $columns
     */
    #[\Override]
    public function build(QueryBuilder $builder): void {
        $right_table = $this->model::getTable();

        /** @var Column */
        $right_key_column = $this->model::getKey();
        $right_key = $right_key_column->name;

        $left_table = $this->owner::getTable();
        $left_key = $this->foreignKey;

        $this->columnPrefix = $this->propertyName() . "__";

        /** @var Column $column */
        foreach($this->model::columns() as $column) {
            $builder->select("{$this->propertyName()}.{$column->name} AS {$this->columnPrefix}{$column->name}");
        }

        $builder->leftJoin(
            "{$right_table} AS {$this->propertyName()}", 
            "{$this->propertyName()}.{$right_key} = {$left_table}.{$left_key}"
        );
    }

    #[\Override]
    public function serialize(object $row): ?Model {
        if($row->{$this->foreignKey} == null) return null;
        return $this->model::of($row, $this->columnPrefix);
    }

    #[Override]
    public function toArray(array &$result, mixed $value): void {
        if($value == null && $this->includeIfNull == false) return;
        $result[$this->propertyName()] = $value instanceof Arrayable ? $value->toArray() : $value;
    }
}
