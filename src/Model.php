<?php

namespace Selvi;

use Closure;
use ReflectionAttribute;
use Selvi\Collection;
use Selvi\Data\DataObject;
use Selvi\Database\Builder\DML\ModelQuery;
use Selvi\Database\Builder\DML\WithBuilder;
use Selvi\Database\Casting\Converter;
use Selvi\Database\Interfaces\IQueryBuilder;
use Selvi\Model\Attributes\Aggregate;
use Selvi\Model\Attributes\BelongsTo;
use Selvi\Model\Attributes\Column;
use Selvi\Model\Attributes\Table;
use Selvi\Model\Interfaces\IAttribute;
use Selvi\Model\Interfaces\IPropertyAttribute;

class Model extends DataObject {

    protected static ?array $attributes_cache = [];
    protected static ?array $properties_cache = [];

    /**
     * @return array<string, object>
     */
    public static function attributes(): array {
        $key = static::class;
        if (isset(static::$attributes_cache[$key]))
            return static::$attributes_cache[$key];

        $reflection = new \ReflectionClass($key);
        $attributes = $reflection->getAttributes();
        $result = [];
        foreach ($attributes as $attribute) {
            $name = $attribute->getName();
            if (isset($result[$name])) continue;

            /** @var IAttribute */
            $obj = $attribute->newInstance();
            $obj->setOwner($key);
            $result[$name] = $obj;
        }
        return static::$attributes_cache[$key] = $result;
    }

    /**
     * @param class-string $classpath
     */
    public static function attribute(string $classpath): ?object {
        $attributes = static::attributes();
        if (!isset($attributes[$classpath])) return null;
        return $attributes[$classpath];
    }

    public static function properties() {
        if (isset(static::$properties_cache[static::class]))
            return static::$properties_cache[static::class];

        $reflection = new \ReflectionClass(static::class);
        $properties = $reflection->getProperties();

        $result = [];
        foreach ($properties as $property) {
            $attributes = $property->getAttributes(IPropertyAttribute::class, ReflectionAttribute::IS_INSTANCEOF);
            foreach ($attributes as $attribute) {
                $attr = $attribute->newInstance();
                $attr->setOwner(static::class);
                $attr->setPropertyName($property->getName());
                $attr->setPropertyType(
                    $property->getType()?->getName(),
                    $property->getType()?->allowsNull()
                );
                $result[$property->getName()] = $attr;
            }
        }
        return static::$properties_cache[static::class] = $result;
    }

    /** @return array<string, BelongsTo | Aggregate> */
    public static function relations(): array {
        return array_filter(static::properties(), fn($prop) => $prop instanceof BelongsTo || $prop instanceof Aggregate);
    }

    public static function relation(string $name): BelongsTo | Aggregate | null {
        $arr = static::relations();
        return isset($arr[$name]) ? $arr[$name] : null;
    }

    /**
     * @return array<string, Column>
     */
    public static function columns() {
        return array_filter(static::properties(), fn($prop) => $prop instanceof Column);
    }

    public static function getTable(): string {
        /**
         * @var Table
         */
        $attribute = static::attribute(Table::class);
        if ($attribute == null) throw new \InvalidArgumentException(
            "Kelas " . static::class . " belum punya atribute Table"
        );
        return $attribute->name;
    }

    public static function getSchema(): string {
        /**
         * @var Table
         */
        $attribute = static::attribute(Table::class);
        if ($attribute == null) throw new \InvalidArgumentException(
            "Kelas " . static::class . " belum punya atribute Table"
        );
        return $attribute->schema;
    }

    public static function getKeyColumn(): ?Column {
        $columns = static::columns();
        foreach ($columns as $column) {
            if ($column->key) return $column;
        }
        return null;
    }

    /** @return ModelQuery<static> */
    public static function query(string $prefix = "", ?Closure $withClosure = null): ModelQuery {
        return new ModelQuery(static::class, prefix: $prefix, withClosure: $withClosure);
    }

    /**
     * @param ?Closure(WithBuilder) $closure
     */
    public static function with(string $name, ?Closure $closure = null) {
        return static::query()->with($name, $closure);
    }

    public static function where(mixed $input): ModelQuery {
        return static::query()->where($input);
    }

    /**
     * @param ?Closure(IQueryBuilder) $query 
     * @return Collection<static> | static | null 
     * */
    public static function find(int | string $id, ?Closure $query = null): Collection | static | null {
        return static::query()->find($id, $query);
    }

    /** @return Collection<static> */
    public static function all(): Collection {
        return static::query()->all();
    }
    
    /** @param \Closure(IQueryBuilder): void $query */
    public static function count(?Closure $query = null): int {
        return static::query()->count($query);
    }

    public static function create(array $data): static {
        $id = static::query()->insert($data);
        $obj = new static();
        $columns = static::columns();
        foreach ($columns as $column) {
            if ($column->key == true) {
                $obj->{$column->propertyName()} = $id;
                continue;
            }
            $obj->{$column->propertyName()} = $data[$column->name];
        }
        return $obj;
    }

    /**
     * @return array<string, scalar>
     */
    public function toArrayDb(): array {
        /**
         * @var array<string, scalar>
         */
        $result = [];
        foreach (static::columns() as $column) {
            $value = !isset($this->{$column->propertyName()}) ? null : Converter::instance()->set(
                type: $column->propertyType(),
                value: $this->{$column->propertyName()},
                cast: $column->cast
            );
            if (
                $value != null ||
                ($value == null && $column->allowsNull())
            ) $result[$column->name] = $value;
        }
        return $result;
    }

    public function update(): bool {
        $keyColumn = static::getKeyColumn();
        $key = $keyColumn->name;
        return static::query()->where([
            [$key, $this->{$keyColumn->propertyName()}]
        ])->update($this->toArrayDb());
    }

    public function delete(): bool {
        $keyColumn = static::getKeyColumn();
        $key = $keyColumn->name;
        return static::query()->where([
            [$key, $this->{$keyColumn->propertyName()}]
        ])->delete();
    }
}
