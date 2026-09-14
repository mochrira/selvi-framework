<?php 

declare(strict_types=1);

namespace Selvi;

use Closure;
use InvalidArgumentException;
use ReflectionClass;
use RuntimeException;
use Selvi\Base;
use Selvi\Contracts\Arrayable;
use Selvi\Database\Attributes\BelongsTo;
use Selvi\Database\Attributes\Column;
use Selvi\Database\Attributes\Table;
use Selvi\Database\Builder\ModelQuery;
use Selvi\Database\Builder\WhereBuilder;
use Selvi\Database\Builder\QueryBuilder;

class Model extends Base implements Arrayable {

    /**
     * Cache instance attribute per class dan per classpath attribute.
     *
     * @var array<string, object|null>
     */
    private static array $attr_cache = [];

    static function get_attr(string $attr_classpath) : ?object {
        $key = static::class . '::' . $attr_classpath;

        // array_key_exists dipakai supaya hasil null (attribute tidak ada) tetap ter-cache.
        if(array_key_exists($key, self::$attr_cache)) {
            return self::$attr_cache[$key];
        }

        $reflection = new ReflectionClass(static::class);
        $attributes = $reflection->getAttributes($attr_classpath);

        return self::$attr_cache[$key] = empty($attributes) ? null : $attributes[0]->newInstance();
    }

    /**
     * Cache metadata property per class.
     *
     * Reflection cukup dijalankan sekali untuk setiap class model, bukan setiap
     * kali metadata dibutuhkan (mis. per baris saat hidrasi di of()).
     *
     * @var array<string, array<string, array{type: ?string, column: ?Column, relation: ?BelongsTo}>>
     */
    private static array $property_cache = [];

    /**
     * Metadata tiap property: tipe, kolom, dan relasinya.
     *
     * @return array<string, array{type: ?string, column: ?Column, relation: ?BelongsTo}>
     */
    static function get_properties() : array {
        $class = static::class;

        if(isset(self::$property_cache[$class])) {
            return self::$property_cache[$class];
        }

        $reflection = new ReflectionClass($class);
        $result = [];
        foreach($reflection->getProperties() as $property) {
            $column_attr = $property->getAttributes(Column::class);
            $relation_attr = $property->getAttributes(BelongsTo::class);

            $result[$property->getName()] = [
                'type' => $property->getType()?->getName(),
                'column' => empty($column_attr) ? null : $column_attr[0]->newInstance(),
                'relation' => empty($relation_attr) ? null : $relation_attr[0]->newInstance(),
            ];
        }

        return self::$property_cache[$class] = $result;
    }

    static function get_table() : string {
        $table = static::get_attr(Table::class);
        if($table === null) {
            throw new InvalidArgumentException(static::class . ' tidak punya attribute Table.');
        }
        return $table->name;
    }

    /**
     * Nama koneksi Manager tempat model ini berada (dari Table::schema).
     */
    static function get_schema() : string {
        $table = static::get_attr(Table::class);
        if($table === null) {
            throw new InvalidArgumentException(static::class . ' tidak punya attribute Table.');
        }
        return $table->schema;
    }

    static function get_key() : ?string {
        foreach(static::get_properties() as $property) {
            if($property['column']?->key) return $property['column']->name;
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

    /**
     * Nama property yang menyimpan kolom key, untuk membaca nilainya dari instance.
     */
    private static function key_property() : string {
        foreach(static::get_properties() as $name => $property) {
            if($property['column']?->key) return $name;
        }
        throw new InvalidArgumentException(static::class . ' tidak punya kolom key. Tambahkan Column(key: true).');
    }

    static function column_names() : array {
        $names = [];
        foreach(static::get_properties() as $property) {
            if($property['column'] !== null) $names[] = $property['column']->name;
        }
        return $names;
    }

    static function relation(string $name) : BelongsTo {
        $property = static::get_properties()[$name] ?? null;
        if($property === null || $property['relation'] === null) {
            throw new InvalidArgumentException("Relasi '{$name}' tidak ditemukan pada " . static::class . '.');
        }
        return $property['relation'];
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
     * Insert satu record, lalu mengembalikan instance model dari data yang diinput
     * (kolom key diisi dari lastInsertId). Tanpa relasi.
     *
     * Hanya data yang dikirim yang diisikan, jadi nilai default database (kolom
     * yang tidak ikut dikirim) belum tercermin di instance. Untuk keadaan sebenarnya
     * dari database, jalurnya dibahas terpisah lewat fresh().
     *
     * Kegagalan insert dilempar sebagai DatabaseException oleh lapisan driver.
     *
     * @param array<string, mixed> $data Data berkunci nama kolom.
     */
    static function create(array $data) : static {
        $id = static::query()->insert($data);

        $model = new static();
        $key_column = static::key_column();

        foreach(static::get_properties() as $name => $property) {
            if($property['column'] === null) continue;

            $column = $property['column']->name;

            if(array_key_exists($column, $data)) {
                $model->{$name} = static::cast_value($property['type'], $data[$column]);
            } elseif($column === $key_column) {
                $model->{$name} = static::cast_value($property['type'], $id);
            }
        }

        return $model;
    }

    /**
     * Menyamakan tipe value dengan tipe property model (int / bool / float).
     */
    private static function cast_value(?string $type, mixed $value) : mixed {
        return match($type) {
            'int'   => (int) $value,
            'bool'  => (bool) $value,
            'float' => (float) $value,
            default => $value,
        };
    }

    /**
     * Membaca ulang record ini dari database sebagai instance baru.
     *
     * Modifier menerima ModelQuery, sehingga relasi bisa diminta lewat with() —
     * satu jalur dengan pembacaan biasa:
     *
     *     $kontak->fresh(function (ModelQuery $query) {
     *         $query->with('grup');
     *     });
     *
     * @param ?Closure(ModelQuery): void $modifier
     * @return static|null null bila record-nya sudah tidak ada.
     * @throws RuntimeException bila instance ini belum punya nilai key.
     */
    function fresh(?Closure $modifier = null) : ?static {
        $key_column = static::key_column();
        $key_property = static::key_property();
        $id = $this->{$key_property};

        if($id === null) {
            throw new RuntimeException(static::class . ' belum punya nilai key sehingga tidak bisa di-fresh.');
        }

        $builder = static::query();
        if($modifier !== null) {
            $modifier($builder);
        }

        /** @var static|null $model */
        $model = $builder->first(fn(QueryBuilder $query) => $query->where([[$key_column, '=', $id]]));

        return $model;
    }

    /**
     * @param array<int, array{relation: string, with: array}> $with AST relasi dari WithBuilder.
     */
    static function of(mixed $item, string $prefix = '', array $with = []) {
        $obj = new static();
        $properties = static::get_properties();

        foreach($properties as $name => $property) {
            if($property['relation'] !== null || $property['column'] === null) continue;

            $key = $prefix . $property['column']->name;

            $obj->{$name} = static::cast_value($property['type'], $item->{$key});
        }

        foreach($with as $node) {
            $name = $node['relation'];
            $property = $properties[$name] ?? null;

            if($property === null || $property['relation'] === null) continue;

            $related = $property['relation']->model;
            $child_prefix = $prefix . $name . '__';
            $related_key = $property['relation']->ownerKey ?? $related::key_column();

            if(($item->{$child_prefix . $related_key} ?? null) === null) {
                $obj->{$name} = null;
                continue;
            }

            $obj->{$name} = $related::of($item, $child_prefix, $node['with']);
        }

        return $obj;
    }

    /**
     * Representasi array model untuk output (mis. jsonResponse).
     *
     * - kolom ditampilkan apa adanya, memakai nama property,
     * - relasi yang terisi dikonversi lewat toArray() model terkait,
     * - relasi bernilai null dihilangkan key-nya (nanti diatur lewat IncludeIfNull),
     * - property yang bukan kolom dan bukan relasi tidak diikutkan.
     */
    #[\Override]
    function toArray() : array {
        $result = [];

        foreach(static::get_properties() as $name => $property) {
            if($property['column'] !== null) {
                $result[$name] = $this->{$name};
                continue;
            }

            if($property['relation'] !== null) {
                $value = $this->{$name};

                if($value === null) continue;

                $result[$name] = $value instanceof Arrayable ? $value->toArray() : $value;
            }
        }

        return $result;
    }

    function toArrayDb() {

    }
    /**
     * Kontak::with('grup')->all();
     */

}