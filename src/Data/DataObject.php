<?php 

namespace Selvi\Data;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;
use Selvi\Data\Interfaces\IDataObject;
use Selvi\Data\Interfaces\IDataProperty;
use Selvi\Database\Casting\Converter;
use Selvi\Interfaces\IArrayable;

class DataObject implements IArrayable, IDataObject {

    private static ?array $cache_properties = [];

    /**
     * @return list<IDataProperty>
     */
    private static function properties() : array {
        if(isset(self::$cache_properties[static::class])) 
            return self::$cache_properties[static::class];

        $reflection = new ReflectionClass(static::class);
        /** @var ReflectionProperty[] */
        $properties = $reflection->getProperties();
        $result = [];
        foreach($properties as $property) {
            if(in_array($property->getName(), ['cache_properties'])) continue;
            $attributes = $property->getAttributes(IDataProperty::class, ReflectionAttribute::IS_INSTANCEOF);
            if(empty($attributes)) continue;
            $instance = $attributes[0]->newInstance();
            $instance->setPropertyName($property->getName());
            $instance->setPropertyType(
                $property->getType()?->getName(),
                $property->getType()?->allowsNull()
            );
            $result[] = $instance;
        }

        return self::$cache_properties[static::class] = $result;
    }

    public static function of(object $item, ?string $prefix = ""): static {
        $obj = new static();
        $row = new DataRow($item, $prefix ?? "");

        foreach(static::properties() as $property) {
            $property->assign($obj, $row);
        }

        return $obj;
    }

    public function toArray(): array {
        /** 
         * Proses inisialisasi :
         * 1. Menyiapkan array result
         * 2. Mengambil semua IDataProperty
         * 3. Mengambil semua properti yang sudah di initialize dari proses ::of
         */
        $result = [];
        $properties = static::properties();
        $vars = get_object_vars($this);
        foreach($properties as $property) {
            /**
             * Properti memiliki 2 kemungkinan
             * 1. Belum diinisialisasi
             *      a. opsional = true, skip
             *      b. opsional = false (default), fallback ke null
             * 2. Sudah diinisialisasi
             */

            $name = $property->propertyName();            

            /** 1. Belum diinisialisasi */
            if(!array_key_exists($name, $vars)) {
                /** 1a. opsional = true */
                if($property->optional) continue;
                /** 1b. opsional = false */
                $result[$name] = null;
                continue;
            }

            if($this->{$name} instanceof IArrayable) {
                $result[$name] = $this->{$name}->toArray();
                continue;
            }

            /**
             * 2. Sudah diinisialisasi, langsung konversi
             */
            $result[$name] = Converter::instance()->serialize(
                type: $property->propertyType(),
                value: $this->{$name},
                cast: $property->cast,
                serialize: $property->serialize
            );
        }
        return $result;
    }

}