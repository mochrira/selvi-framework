<?php 

namespace Selvi\Data;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;
use Selvi\Contracts\Arrayable;
use Selvi\Database\Casting\Converter;
use Selvi\Model\Attributes\Aggregate;
use Selvi\Model\Attributes\BelongsTo;
use Selvi\Model\Interfaces\IPropertyAttribute;

class DataObject implements Arrayable {

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

    private static function hasPrefixedColumn(object $item, ?string $prefix): bool {
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

    public static function of(object $item, ?string $prefix = "") {
        $obj = new static();
        /** Mengambil semua properties */
        $properties = static::properties(); 
        foreach($properties as $index => $property) {
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
                $hasPrefixedCol = static::hasPrefixedColumn($item, "{$prefix}{$property->propertyName()}_");
                if($hasPrefixedCol) {
                    $obj->{$property->propertyName()} = $property->propertyType()::of($item, "{$prefix}{$property->propertyName()}_", false);
                }
                continue;
            }

            if($property instanceof Aggregate) {
                $hasPrefixedCol = static::hasPrefixedColumn($item, "{$prefix}{$property->query}_");
                if($hasPrefixedCol) {
                    $obj->{$property->propertyName()} = $item->{"{$prefix}{$property->query}__{$property->column}"};
                }
                continue;
            }

            if($property instanceof DataProperty && $property->prefix != null) {
                static::log("{$index}. Mencoba konfigurasi child prefix");
                if(static::hasPrefixedColumn($item, "{$property->prefix}_") && 
                    is_subclass_of($property->propertyType(), DataObject::class)) {
                    $obj->{$property->propertyName()} = $property->propertyType()::of($item, "{$prefix}{$property->prefix}_");
                    continue;
                }
                static::log("{$index}. Gagal mencoba konfigurasi child prefix");
            }

            $source = "{$property->name}";
            if($prefix != "") $source = "{$prefix}_{$source}";
            /** Kesalahan user, name/prefix harus diisi salah satunya */
            if($source == "_") continue;

            /**
             * 1. Jika properti tidak ditemukan (baik yang memiliki prefix maupun tidak), maka ada 2 kemungkinan:
             *      a. Properti merupakan nested DataObject
             *      b. Properti diijinkan null (allowsNull == true)
             *      c. Properti memang tidak ada di $item (ditinggalkan uninitialized), serahkan ke toArray
             */
            if(!property_exists($item, $source)) {
                static::log("{$index}. Property {$source} tidak ditemukan");
                if($property->allowsNull()) {
                    /** 1b. properti mengijinkan null */
                    static::log("{$index}. Property {$source} mengijinkan null, setting to null");
                    $obj->{$property->propertyName()} = null;
                } else {
                    /** 1c. properti memang tidak ada di $item */
                    static::log("{$index}. Property {$source} tidak ditemukan, setting uninitialized");
                }
                continue;
            }

            /**
             * 2. Jika properti ditemukan, ada 2 kemungkinan:
             *      a. Properti diassign apa adanya
             *      b. Properti bertipe DataObject, jadi harus memanggil ::of
             */
            static::log("{$index}. Property {$source} ditemukan");

            /** 2a. Di assign apa adanya dulu */
            $value = Converter::instance()->get(
                type: $property->propertyType(),
                value: $item->{$source},
                cast: $property->cast
            );

            if(is_subclass_of($property->propertyType(), DataObject::class)) {
                /** 2b. Jika properti bertipe DataObject */
                static::log("{$index}. Property {$source} merupakan data object, panggil ::of");
                $value = $property->propertyType()::of($value);
            }

            static::log("{$index}. Properti {$source} diassign ke object");
            $obj->{$property->propertyName()} = $value;
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

            if($this->{$name} instanceof Arrayable) {
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