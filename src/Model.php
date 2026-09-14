<?php 

declare(strict_types=1);

namespace Selvi;

use Closure;
use InvalidArgumentException;
use ReflectionClass;
use Selvi\Base;
use Selvi\Database\Attributes\BelongsTo;
use Selvi\Database\Attributes\Column;
use Selvi\Database\Attributes\Table;
use Selvi\Database\Builder\ModelQuery;
use Selvi\Database\Builder\WhereBuilder;
use Selvi\Database\Manager;
use Selvi\Database\Builder\QueryBuilder;

class Model extends Base {

    static function get_attr(string $attr_classpath) {
        $reflection = new ReflectionClass(static::class);
        $attributes = $reflection->getAttributes($attr_classpath);
        if(!isset($attributes)) return null;
        return $attributes[0]->newInstance();
    }

    static function get_properties() {
        $reflection = new ReflectionClass(static::class);
        $result = [];
        foreach($reflection->getProperties() as $property) {
            $instance = new ModelProperty();
            $instance->type = $property->getType()?->getName();

            $column_attr = $property->getAttributes(Column::class);
            if(!empty($column_attr)) {
                $instance->column = $column_attr[0]->newInstance();
            }

            $relation_attr = $property->getAttributes(BelongsTo::class);
            if(!empty($relation_attr)) {
                $instance->relation = $relation_attr[0]->newInstance();
            }

            $result[$property->getName()] = $instance;
        }
        return $result;
    }

    static function get_table() : string {
        return static::get_attr(Table::class)->name;
    }

    static function get_key() : ?string {
        foreach(static::get_properties() as $property) {
            if($property->column?->key) return $property->column->name;
        }
        return null;
    }

    static function key_column() : string {
        $key = static::get_key();
        if($key === null) {
            throw new InvalidArgumentException(static::class . ' tidak punya kolom key. Tambahkan Column(key: true).');
        }
        return $key;
    }

    static function column_names() : array {
        $names = [];
        foreach(static::get_properties() as $property) {
            if($property->column !== null) $names[] = $property->column->name;
        }
        return $names;
    }

    static function relation(string $name) : BelongsTo {
        $property = static::get_properties()[$name] ?? null;
        if($property?->relation === null) {
            throw new InvalidArgumentException("Relasi '{$name}' tidak ditemukan pada " . static::class . '.');
        }
        return $property->relation;
    }

    static function query() : ModelQuery {
        return new ModelQuery(static::class);
    }

    static function with(string $relation, ?Closure $nest = null) : ModelQuery {
        return static::query()->with($relation, $nest);
    }

    /**
     * Mencari berdasarkan kolom key.
     *
     * - satu id  -> model tunggal (atau null)
     * - array id -> Collection
     */
    static function find(mixed $id) : Model | Collection | null {
        $key = static::key_column();

        if(is_array($id)) {
            if(empty($id)) return new Collection();

            return static::query()->all(function(QueryBuilder $query) use ($key, $id) {
                $query->where(function(WhereBuilder $builder) use ($key, $id) {
                    foreach($id as $value) {
                        $builder->orWhere([[$key, '=', $value]]);
                    }
                });
            });
        }

        return static::query()->first(fn(QueryBuilder $query) => $query->where([[$key, '=', $id]]));
    }

    static function all() : Collection {
        return static::query()->all();
    }

    /**
     * @param array<int, array{relation: string, with: array}> $with AST relasi dari WithBuilder.
     */
    static function of(mixed $item, string $prefix = '', array $with = []) {
        $obj = new static();
        $properties = static::get_properties();

        foreach($properties as $name => $property) {
            if($property->relation !== null || $property->column === null) continue;

            $key = $prefix . $property->column->name;

            switch ($property->type) {
                case "int": $obj->{$name} = (int)$item->{$key};
                    break;
                case "bool": $obj->{$name} = (bool)$item->{$key};
                    break;
                case "float": $obj->{$name} = (float)$item->{$key};
                    break;
                default: $obj->{$name} = $item->{$key};
            }
        }

        foreach($with as $node) {
            $name = $node['relation'];
            $property = $properties[$name] ?? null;

            if($property?->relation === null) continue;

            $related = $property->relation->model;
            $child_prefix = $prefix . $name . '__';
            $related_key = $property->relation->ownerKey ?? $related::key_column();

            if(($item->{$child_prefix . $related_key} ?? null) === null) {
                $obj->{$name} = null;
                continue;
            }

            $obj->{$name} = $related::of($item, $child_prefix, $node['with']);
        }

        return $obj;
    }

    function toArray() {
        
    }

    function toArrayDb() {

    }
    /**
     * Kontak::with('grup')->all();
     */

}