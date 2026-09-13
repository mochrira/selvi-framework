<?php 

declare(strict_types=1);

namespace Selvi;

use ReflectionClass;
use Selvi\Base;
use Selvi\Database\Attributes\Column;
use Selvi\Database\Attributes\Table;
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

            $result[$property->getName()] = $instance;
        }
        return $result;
    }

    static function all() : Collection {
        $table_attribute = static::get_attr(Table::class);
        $properties = static::get_properties();

        $column_properties = array_filter(array_values($properties), fn(ModelProperty $property) => isset($property->column));
        $columns = array_map(fn ($property) => $table_attribute->name . '.'. $property->column->name, $column_properties);

        $result = DB::table($table_attribute->name)->select($columns)->get()->result();
        return Collection::fromMap(fn(mixed $item) => self::of($item), $result);
    }

    /**
     * @param ?Callable(T) $includes
     */
    static function of(mixed $item, string $prefix = '', ?Callable $includes = null) {
        $obj = new static();
        $columns = static::get_properties();
        foreach($columns as $name => $property) {
            switch ($property->type) {
                case "int": $obj->{$name} = (int)$item->{$property->column->name};
                    break;
                default: $obj->{$name} = $item->{$property->column->name};
            }
        }
        return $obj;
    }

    function toArray() {
        
    }

    function toArrayDb() {

    }
    /**
     * Kontak::with('grup')->get();
     */

}