<?php 

namespace Selvi\Data;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;
use Selvi\Data\Interfaces\IDataObject;
use Selvi\Data\Interfaces\IPropertyAttribute;
use Selvi\Database\Casting\Converter;
use Selvi\Interfaces\IArrayable;
use Selvi\Model\Attributes\Aggregate;
use Selvi\Model\Attributes\BelongsTo;

class DataObject implements IArrayable, IDataObject {

    private static ?array $cache_properties = [];
    private static $debug = false;

    private static function log(string $text) {
        if(static::$debug == false) return;
        echo $text."\n";
    }

    /**
     * @return list<DataProperty>
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
            $attributes = $property->getAttributes(IPropertyAttribute::class, ReflectionAttribute::IS_INSTANCEOF);
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

    private static function hasPrefixedKey(object $item, ?string $prefix): bool {
        /**
         * Periksa 2 kemungkinan : 
         * 1. Prefix kosong, tidak punya prefixed column
         * 2. Temukan 1 item saja yang ber-prefix, lalu return true
         */
        if($prefix === null || $prefix === '') return false;
        foreach(get_object_vars($item) as $key => $val) {
            /** jika ada 1 saja nama properti yang ber-prefix, return true */
            if($val !== null && str_starts_with($key, $prefix)) return true;
        }

        /** Jika sampai sini berarti tidak ada property dengan prefix tersebut */
        return false;
    }

    public static function of(object $item, ?string $prefix = ""): static {
        $obj = new static();
        /** Mengambil semua properties */
        $properties = static::properties(); 
        foreach($properties as $index => $property) {
            $source = "{$property->name}";
            if($prefix != "") $source = "{$prefix}_{$source}";

            /**
             * 1. Jika properti TIDAK DITEMUKAN
             *      a. Jika properti tidak ditemukan, kemungkinan DataObject dengan prefix
             *      b. Jika tetap tidak ada, maka dipastikan nullable atau un-instantiable
             */
            if(!property_exists($item, $source)) {
                static::log("{$index}. Property {$source} tidak ditemukan");

                if(is_subclass_of($property->propertyType(), DataObject::class) && ($property->prefix != null)) {
                    static::log("{$index}. Mencoba konfigurasi child prefix");
                    if(static::hasPrefixedKey($item, "{$prefix}{$property->prefix}_")) {
                        $obj->{$property->propertyName()} = $property->propertyType()::of($item, "{$prefix}{$property->prefix}_");
                        continue;
                    }
                    static::log("{$index}. Gagal mencoba konfigurasi child prefix");
                }

                /** 1a. mengijinkan null */
                if($property->allowsNull()) {
                    static::log("{$index}. Property {$source} mengijinkan null, setting to null");
                    $obj->{$property->propertyName()} = null;
                } else {
                    /** 1b. tinggalkan tanpa inisialisasi */
                    static::log("{$index}. Property {$source} tidak ditemukan, setting uninitialized");
                }
                continue;
            }

            /**
             * 2. Jika properti DITEMUKAN
             *      a. Periksa apakah DTO bersarang
             *      b. Jika tidak / scalar, langsung convert value
             */
            static::log("{$index}. Property {$source} ditemukan");

            $value = $item->{$source};

            /** 2a. Jika properti bertipe DataObject */
            if(is_subclass_of($property->propertyType(), DataObject::class)) {
                if($value !== null) {
                    static::log("{$index}. Property {$source} merupakan data object, panggil ::of");
                    $value = $property->propertyType()::of($value);
                }
                $obj->{$property->propertyName()} = $value;
                continue;
            }

            /** 2b. langsung convert value */
            $value = Converter::instance()->get(
                type: $property->propertyType(),
                value: $value,
                cast: $property->cast
            );

            static::log("{$index}. Properti {$source} diassign ke object");
            $obj->{$property->propertyName()} = $value;
            continue;

            /**
             * prefix diasumsikan string kosong jika tidak diisi, 
             * sehingga aman untuk menambahkannya sebagai prefix dari source
             * 
             * Nama properti ada 2 penggunaan:
             * 1. Nama langsung (contoh. id_kontak)
             * 2. Nama dengan child prefix (contoh. grup__id_grup)
             */

            if($property instanceof BelongsTo) {
                /**
                 * Simulasi prefix, semisal objek kontak sebagai berikut :
                 * 
                 * class Kontak {
                 *      ?int $idKontak,
                 *      ?string $nmKontak,
                 *      ?Departemen $departemen,
                 *      ?Unit $unit
                 * }
                 * 
                 * dan value dari $item sebagai berikut :
                 * 
                 * { 
                 *      "id_kontak" : 1, 
                 *      "nm_kontak": "Halo", 
                 *      "departemen__id_departemen" : 1, 
                 *      "departemen__nm_departemen": "Sales",
                 *      "unit__id_unit": 1,
                 *      "unit__nm_unit": "Marketing",
                 *      "unit__id_departemen": 1,
                 *      "unit_departemen__id_departemen: 1,
                 *      "unit_departemen__nm_departemen: "Sales"
                 * }
                 * 
                 * pertama ::of dipanggil, prefix kosong sehingga tepat pada properti ke 3 yaitu departemen, 
                 * terdeteksi relation attribute panggil Departemen::of dengan nilai prefix "{prefix}{propertyName}_", 
                 * berlaku rekursif sehingga prefix akan terus dibawa sampai ke anak2nya
                 * 
                 */
                $hasPrefixedCol = static::hasPrefixedKey($item, "{$prefix}{$property->propertyName()}_");
                if($hasPrefixedCol) {
                    $obj->{$property->propertyName()} = $property->propertyType()::of($item, "{$prefix}{$property->propertyName()}_", false);
                }
                continue;
            }

            if($property instanceof Aggregate) {
                $hasPrefixedCol = static::hasPrefixedKey($item, "{$prefix}{$property->query}_");
                if($hasPrefixedCol) {
                    $obj->{$property->propertyName()} = $item->{"{$prefix}{$property->query}__{$property->column}"};
                }
                continue;
            }

            
        }
        return $obj;
    }

    public function toArray(): array {
        /** 
         * Proses inisialisasi :
         * 1. Menyiapkan array result
         * 2. Mengambil semua DataProperty
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