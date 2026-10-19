<?php 

declare(strict_types=1);

namespace Selvi;

use Closure;
use Selvi\Base;
use Selvi\Contracts\Arrayable;
use Selvi\Model\Attributes\Table;
use Selvi\Database\Builder\DML\ModelQuery;
use Selvi\Database\Builder\DML\QueryBuilder;
use Selvi\Database\Builder\DML\WithBuilder;
use Selvi\Database\Casting\Converter;
use Selvi\Model\Attributes\Column;
use Selvi\Model\Interfaces\IAggregateAttribute;
use Selvi\Model\Interfaces\IPropertyAttribute;
use Selvi\Model\Interfaces\IRelationAttribute;
use Selvi\Model\RawProperty;

class Model extends Base implements Arrayable {

    /**
     * ==== Start of Optimized Model
     */

    /**
     * 
     * Cache are shared accross all registered models.
     * So, it saved with following format :
     * 
     * $attributes_cache = [
     *      "Selvi\Tests\Models\Kontak": [
     *          "Selvi\Model\Attributes\Table": IAttribute,
     *          ...
     *      ],
     *      "Selvi\Tests\Models\Grup": [
     *          "Selvi\Model\Attributes\Table": IAttribute,
     *          ...
     *      ]
     * ]
     * 
     * @var ?array<string, array<string, IAttribute>> $attributes_cache
     */
    protected static ?array $attributes_cache = null;

    /**
     * 
     * $properties_cache = [
     *      "Selvi\Tests\Models\Kontak": [
     *          "idKontak": RawProperty,
     *          "nmKontak": RawProperty,
     *          ...
     *      ],
     *      "Selvi\Tests\Models\Grup": [
     *          "idGrup": RawProperty,
     *          "nmGrup": RawProperty,
     *          ...
     *      ]
     * ]
     * 
     * @var ?array<string, array<string, RawProperty>> $properties_cache
     */
    protected static ?array $properties_cache = null;

    /**
     * 
     * $columns_cache = [
     *      "Selvi\Tests\Models\Kontak": [
     *          "idKontak": Column,
     *          "nmKontak": Column
     *          ...
     *      ]
     *      "Selvi\Tests\Models\Grup": [
     *          "idGrup": Column,
     *          "nmGrup": Column
     *          ...
     *      ]
     * ]
     * 
     * @var ?array<string, array<string, Column>> $columns_cache
     */
    protected static ?array $columns_cache = null;

    /**
     * 
     * $relations_cache = [
     *      "Selvi\Tests\Models\Kontak": [
     *          "grup": IRelationAttribute,
     *          ...
     *      ]
     *      "Selvi\Tests\Models\Grup": [
     *          "kontak": IRelationAttribute,
     *          ...
     *      ]
     * ]
     * 
     * @var ?array<string, array<string, IRelationAttribute>> $relations_cache
     */
    protected static ?array $relations_cache = null;

    /**
     * 
     * $aggregates_cache = [
     *      "Selvi\Tests\Models\Departemen": [
     *          "jmlKontak": IAggregateAttribute,
     *          "totalSales": IAggregateAttribute
     *      ],
     *      "Selvi\Tests\Models\Profesi": [
     *          "jmlKontak": IAggregateAttribute,
     *          "totalSales": IAggregateAttribute
     *      ]
     * ]
     * 
     */
    protected static ?array $aggregates_cache = null;

    /**
     * @return array<string, object>
     */
    public static function attributes(): array {
        $key = static::class;
        if(isset(static::$attributes_cache[$key])) 
            return static::$attributes_cache[$key];

        $reflection = new \ReflectionClass($key);
        $attributes = $reflection->getAttributes();
        $result = [];
        foreach($attributes as $attribute) {
            $name = $attribute->getName();
            if(isset($result[$name])) continue;

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
        if(!isset($attributes[$classpath])) return null;
        return $attributes[$classpath];
    }

    /**
     * @return array<string, RawProperty>
     */
    public static function properties(): array {
        $key = static::class;
        if(isset(static::$properties_cache[$key]) && static::$properties_cache[$key] != null) 
            return static::$properties_cache[$key];
        
        $reflection = new \ReflectionClass(static::class);
        $properties = $reflection->getProperties();
        $excluded = [
            "properties_cache", "attributes_cache", 
            "columns_cache", "relations_cache", "aggregates_cache"
        ];

        /**
         * @var array<string, RawProperty>
         */
        $result = [];
        foreach($properties as $property) {
            $name = $property->getName();
            if(isset($result[$name]) || in_array($name, $excluded)) continue;

            $result[$name] = new RawProperty(
                name: $property->getName(),
                type: $property->getType()?->getName(),
                attributes: array_map(function ($attribute) use ($key, $property) {
                    /** @var IPropertyAttribute */
                    $obj = $attribute->newInstance();
                    $obj->setOwner($key);
                    $obj->setPropertyName($property->getName());
                    $obj->setPropertyType($property->getType()?->getName());
                    return $obj;
                }, $property->getAttributes())
            );
        }
        return static::$properties_cache[$key] = $result;
    }

    public static function property(string $name): ?RawProperty {
        $properties = static::properties();
        if(!isset($properties[$name])) return null;
        return $properties[$name];
    }

    /**
     * @return array<string, Column>
     */
    public static function columns(): array {
        $key = static::class;
        if(isset(static::$columns_cache[$key]) && static::$columns_cache[$key] != null) 
            return static::$columns_cache[$key];

        $result = [];
        foreach(static::properties() as $property) {
            $column = $property->column();
            if($column == null) continue;
            $result[$property->name()] = $column;
        }
        return static::$columns_cache[$key] = $result;
    }
    
    public static function column(string $name): ?Column {
        $columns = static::columns();
        if(!isset($columns[$name])) return null;
        return $columns[$name];
    }

    /**
     * @return array<string, IRelationAttribute>
     */
    public static function relations(): array {
        $key = static::class;
        if(isset(static::$relations_cache[$key]) && static::$relations_cache[$key] != null) 
            return static::$relations_cache[$key];

        $result = [];
        foreach(static::properties() as $property) {
            $relation = $property->relation();
            if($relation == null) continue;
            $result[$property->name()] = $relation;
        }
        return static::$relations_cache[$key] = $result;
    }
    
    public static function relation(string $name): ?IRelationAttribute {
        $relations = static::relations();
        if(!isset($relations[$name])) return null;
        return $relations[$name];
    }

    /**
     * @return array<string, IAggregateAttribute>
     */
    public static function aggregates(): array {
        $key = static::class;
        if(isset(static::$relations_cache[$key]) && static::$relations_cache[$key] != null) 
            return static::$relations_cache[$key];

        $result = [];
        foreach(static::properties() as $property) {
            $relation = $property->aggregate();
            if($relation == null) continue;
            $result[$property->name()] = $relation;
        }
        return static::$relations_cache[$key] = $result;
    }
    
    public static function aggregate(string $name): ?IAggregateAttribute {
        $relations = static::aggregates();
        if(!isset($relations[$name])) return null;
        return $relations[$name];
    }
    

    public static function getTable(): string {
        /**
         * @var Table
         */
        $attribute = static::attribute(Table::class);
        if($attribute == null) throw new \InvalidArgumentException("Kelas ".static::class." belum punya atribute Table");
        return $attribute->name;
    }

    public static function getSchema(): string {
        /**
         * @var Table
         */
        $attribute = static::attribute(Table::class);
        if($attribute == null) throw new \InvalidArgumentException("Kelas ".static::class." belum punya atribute Table");
        return $attribute->schema;
    }

    public static function getKey(): Column {
        $columns = static::columns();
        $key_columns = array_values(array_filter($columns, function($column) {
            return $column->key;
        }));
        if(empty($key_columns)) throw new \InvalidArgumentException("Kelas ".static::class." tidak punya kolom kunci");
        return $key_columns[0];
    }

    /**
     * @param array<int, array{relation: string, with: array}> $with AST relasi dari WithBuilder.
     */
    static function of(mixed $item, array $paths = [], array $withClauses = []) {
        $obj = new static();

        foreach(static::properties() as $name => $property) {
            $prefix = join("_", $paths);

            if($property->column() != null) {
                $column = $property->column();
                $obj->{$column->getPropertyName()} = static::converter()->get(
                    type: $column->getPropertyType(), 
                    value: $item->{"{$prefix}__{$column->name}"}, 
                    cast: $column->cast
                );
                continue;
            }

            if($property->relation() != null) {
                $relation = $property->relation();
                if($item->{"{$prefix}__{$relation->foreignKey}"} == null) continue;
                if($item->{"{$prefix}_{$name}__{$relation->foreignKey}"} == null) continue;
                
                $withKeys = array_keys($withClauses);
                if(!in_array($name, $withKeys)) continue;
                
                $withBuilder = new WithBuilder();
                if($withClauses[$name] != null) $withClauses[$name]($withBuilder);
                $obj->{$name} = $relation->model::of($item, [...$paths, $name], $withBuilder->clauses);
                continue;
            }

            if($property->aggregate() != null) {
                $aggregate = $property->aggregate();
                $obj->{$aggregate->getPropertyName()} = static::converter()->get(
                    type: $aggregate->getPropertyType(), 
                    value: $item->{"{$prefix}__{$aggregate->column}"},
                    cast: 
                );
            }
        }

        return $obj;
    }


    /**
     * @return array<string, mixed>
     */
    #[\Override]
    function toArray() : array {

        /** @var array<string, mixed> */
        $result = [];

        foreach(static::properties() as $propertyName => $property) {
            if($property->column() !== null) {
                $result[$propertyName] = static::converter()->serialize(
                    type: $property->type(), 
                    value: $this->{$propertyName}, 
                    cast: $property->column()->cast, 
                    serialize: $property->column()->serialize);
                continue;
            }

            if($property->relation() != null) {
                $value = $this->{$propertyName};
                if(!isset($value) && $property->relation()->includeIfNull === false) continue;
                $result[$propertyName] = $value;
                continue;
            }

            if($property->notMapped() !== null) {
                $value = $this->{$propertyName};
                $notMapped = $property->notMapped();
                if($notMapped->includeIfNull === true || ($notMapped->includeIfNull === false && $value !== null)) {
                    $result[$propertyName] = $value instanceof Arrayable ? $value->toArray() : $value;
                }
            }
        }

        return $result;
    }

    /**
     * @return ModelQuery<static>
     */
    static function query(array $paths = []) : ModelQuery {
        return new ModelQuery(static::class, $paths);
    }

    /**
     * @param ?\Closure(QueryBuilder): void $query
     * @return Collection<static>
     */
    static function all(?\Closure $query = null) : Collection {
        return static::query()->all($query);
    }

    /**
     * @param ?\Closure(QueryBuilder): void $query
     */
    static function count(?\Closure $query = null) : int {
        return static::query()->count($query);
    }

    /**
     * @param mixed $id
     * @return static|Collection<static>|null
     * @phpstan-return ($id is array ? Collection<static> : static|null)
     * @psalm-return ($id is array ? Collection<static> : static|null)
     */
    static function find(mixed $id) : static | Collection | null {
        return static::query()->find($id);
    }

    /**
     * @param ?\Closure(QueryBuilder): void $query
     */
    static function first(?\Closure $query = null) : ?static {
        return static::query()->first($query);
    }

    static function converter() : Converter {
        return Converter::instance();
    }

    /**
     * @param ?\Closure(QueryBuilder): void $modifier
     */
    function fresh(?\Closure $modifier = null) : ?static {
        $keyColumn = static::getKey();
        $id = $this->{$keyColumn->getPropertyName()};
        if($id === null) 
            throw new \RuntimeException(static::class . ' belum punya nilai key');

        $builder = static::query();
        if($modifier !== null) $modifier($builder);

        /** @var static|null $model */
        $model = $builder->first(fn($query) => $query->where([[$keyColumn->name, '=', $id]]));
        return $model;
    }

    /**
     * @return ModelQuery<static>
     */
    static function where(mixed $input) : ModelQuery {
        return static::query()->where($input);
    }

    /**
     * @return ModelQuery<static>
     */
    static function join(string $table, string $on) : ModelQuery {
        return static::query()->join($table, $on);
    }

    /**
     * @return ModelQuery<static>
     */
    static function innerJoin(string $table, string $on) : ModelQuery {
        return static::query()->innerJoin($table, $on);
    }

    /**
     * @return ModelQuery<static>
     */
    static function leftJoin(string $table, string $on) : ModelQuery {
        return static::query()->leftJoin($table, $on);
    }

    /**
     * @return ModelQuery<static>
     */
    static function rightJoin(string $table, string $on) : ModelQuery {
        return static::query()->rightJoin($table, $on);
    }

    /**
     * @param array<string, mixed> $data Data berkunci nama kolom.
     */
    static function create(array $data) : static {
        $id = static::query()->insert($data);

        $model = new static();
        $key = static::getKey();

        foreach(static::columns() as $column) {
            if(array_key_exists($column->name, $data)) {
                $model->{$column->getPropertyName()} = static::converter()->get(
                    type: $column->getPropertyType(), 
                    value: $data[$column->name], 
                    cast: $column->cast
                );
            } elseif($column->getPropertyName() === $key->getPropertyName()) {
                $model->{$column->getPropertyName()} = static::converter()->get(
                    type: $column->getPropertyType(), 
                    value: $id, 
                    cast: $column->cast
                );
            }
        }

        return $model;
    }

    /**
     * @return array<string, scalar>
     */
    public function toArrayDb() : array {
        /**
         * @var array<string, scalar>
         */
        $result = [];
        foreach(static::columns() as $column) {
            if($column->nullable == true || ($column->nullable == false && isset($this->{$column->getPropertyName()}))) {
                $result[$column->name] = static::converter()->set(
                    type: $column->getPropertyType(), 
                    value: $this->{$column->getPropertyName()}, 
                    cast: $column->cast
                );
            }
        }
        return $result;
    }

    /**
     * @param ?array<string, mixed> $data Data baru opsional.
     */
    public function update(?array $data = null): bool {
        $keyColumn = static::getKey();
        $keyValue = $this->{$keyColumn->getPropertyName()} ?? null;
        if ($keyValue === null) 
            throw new \LogicException("Model " . static::class . " tidak memiliki nilai primary key untuk di-update.");

        if ($data !== null) {
            foreach(static::columns() as $column) {
                if(!array_key_exists($column->name, $data)) continue;
                $this->{$column->getPropertyName()} = static::converter()->get(
                    type: $column->getPropertyType(), 
                    value: $data[$column->name], 
                    cast: $column->cast
                );
            }
        }

        $dbData = $this->toArrayDb();
        unset($dbData[$keyColumn->name]);

        return static::query()
            ->where([[$keyColumn->name, '=', $keyValue]])
            ->update($dbData);
    }

     /**
     * Hapus record model ini dari database (Mode 3: Active Record Instance).
     */
    public function delete(): bool {
        $keyColumn = static::getKey();
        $keyValue = $this->{$keyColumn->getPropertyName()} ?? null;
        if ($keyValue === null) 
            throw new \LogicException("Model " . static::class . " tidak memiliki nilai primary key untuk di-update.");

        return static::query()
            ->where([[$keyColumn->name, '=', $keyValue]])
            ->delete();
    }

    /**
     * @return ModelQuery<static>
     */
    static function with(string $name, ?Closure $closure = null) : ModelQuery {
        return static::query()->with($name, $closure);
    }

}