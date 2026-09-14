<?php

namespace Selvi\Database\Builder\DML;

use Closure;
use RuntimeException;
use Selvi\Collection;
use Selvi\Database\Contracts\ConnectionInterface;
use Selvi\Database\DatabaseManager;
use Selvi\Database\DB;
use Selvi\Model;

/**
 * Query untuk model: menyusun JOIN dari relasi `with()` lalu menghidrasi baris
 * hasil query menjadi objek model (termasuk property relasi).
 *
 * Alias tabel dan prefix kolom diturunkan dari path relasi, mis. `grup` dan
 * `grup__wilayah`, sehingga antar tabel tidak ada nama kolom yang bertabrakan.
 *
 * @template T of \Selvi\Model
 */
class ModelQuery {

    /**
     * @var class-string<T>
     */
    private string $model;

    private WithBuilder $with;

    private WhereBuilder $where;

    /**
     * @param class-string<T> $model
     */
    public function __construct(string $model) {
        $this->model = $model;
        $this->with = new WithBuilder();
        $this->where = new WhereBuilder();
    }

    public function with(string $relation, ?Closure $nest = null): static {
        $this->with->with($relation, $nest);
        return $this;
    }

    public function where(mixed $input): static {
        $this->where->where($input);
        return $this;
    }

    public function orWhere(mixed $input): static {
        $this->where->orWhere($input);
        return $this;
    }

    /**
     * Mengambil semua baris sebagai Collection model.
     *
     * Callback $query dijalankan setelah tabel, kolom, dan JOIN relasi terpasang,
     * sehingga alias relasi (mis. "grup.nmGrup") bisa dipakai di dalamnya.
     *
     * @param ?Closure(QueryBuilder): void $query
     * @return Collection<T>
     */
    public function all(?Closure $query = null): Collection {
        $model = $this->model;
        $with = $this->with->toArray();

        return Collection::fromMap(fn(mixed $item) => $model::of($item, '', $with), $this->rows($query));
    }

    /**
     * Mengambil baris pertama sebagai model, atau null bila tidak ada.
     *
     * LIMIT 1 dipasang setelah callback supaya hasilnya tidak pernah lebih dari
     * satu baris, berapa pun limit yang diatur di dalam callback.
     *
     * @param ?Closure(QueryBuilder): void $query
     * @return T|null
     */
    public function first(?Closure $query = null): ?Model {
        $model = $this->model;
        $with = $this->with->toArray();
        $rows = $this->rows($query, 1);

        if (empty($rows)) return null;

        return $model::of($rows[0], '', $with);
    }

    /**
     * Mencari berdasarkan kolom key, memakai relasi yang sudah dikonfigurasi.
     *
     * - satu id  -> model tunggal (atau null)
     * - array id -> Collection
     *
     * @param mixed $id
     * @return T|Collection<T>|null
     * @phpstan-return ($id is array ? Collection<T> : T|null)
     * @psalm-return ($id is array ? Collection<T> : T|null)
     */
    public function find(mixed $id) : Model | Collection | null {
        $model = $this->model;
        $key = $model::key_column();

        if(is_array($id)) {
            if(empty($id)) return new Collection();

            return $this->all(function(QueryBuilder $query) use ($key, $id) {
                $query->where(function(WhereBuilder $builder) use ($key, $id) {
                    foreach($id as $value) {
                        $builder->orWhere([[$key, '=', $value]]);
                    }
                });
            });
        }

        return $this->first(fn(QueryBuilder $query) => $query->where([[$key, '=', $id]]));
    }

    /**
     * Menghitung jumlah record, berguna untuk pagination.
     *
     * Yang dihitung adalah kolom key model (Lihat Model::key_column()), bukan
     * COUNT(*), supaya hasilnya tetap jumlah record model meski ada JOIN.
     * Hasil COUNT dikembalikan pada kolom beralias "total".
     *
     * Catatan: bila callback memakai GROUP BY, satu query menghasilkan satu COUNT
     * per grup sehingga nilai yang dikembalikan bukan total baris.
     *
     * @param ?Closure(QueryBuilder): void $query
     */
    public function count(?Closure $query = null): int {
        $model = $this->model;
        $base = $model::get_table();
        $key = $model::key_column();

        $row = $this->builder($query, ["COUNT({$base}.{$key}) AS total"])->get()->row();

        return is_object($row) ? (int) $row->total : 0;
    }

    /**
     * Insert satu record dan mengembalikan kolom key-nya.
     *
     * SQL disusun QueryBuilder + Grammar. Key yang dikirim eksplisit di $data
     * dipakai apa adanya (mis. UUID).
     *
     * Kegagalan insert dilempar sebagai DatabaseException oleh lapisan driver.
     *
     * @param array<string, mixed> $data Data berkunci nama kolom.
     */
    public function insert(array $data) : int | string {
        $model = $this->model;

        $id = DB::table($model::get_table())
            ->useConnection($this->connection($model))
            ->insert($data);

        $key = $model::key_column();

        return $data[$key] ?? $id;
    }

    /**
     * Update record yang sesuai kondisi WHERE dan mengembalikan status keberhasilan.
     *
     * @param array<string, mixed> $data Data berkunci nama kolom.
     */
    public function update(array $data): bool {
        $model = $this->model;
        $builder = DB::table($model::get_table())->useConnection($this->connection($model));

        if ($this->where->hasWheres()) {
            $builder->where($this->where);
        }

        return $builder->update($data);
    }

    /**
     * Hapus record yang sesuai kondisi WHERE dan mengembalikan status keberhasilan.
     */
    public function delete(): bool {
        $model = $this->model;
        $builder = DB::table($model::get_table())->useConnection($this->connection($model));

        if ($this->where->hasWheres()) {
            $builder->where($this->where);
        }

        return $builder->delete();
    }

    /**
     * Koneksi (Table::schema) model, divalidasi terdaftar di DatabaseManager.
     *
     * @param class-string<Model> $model
     * @throws RuntimeException bila koneksinya tidak terdaftar.
     */
    private function connection(string $model) : ConnectionInterface {
        $schema = $model::get_schema();

        if(!DatabaseManager::has($schema)) {
            throw new RuntimeException("Koneksi '{$schema}' tidak terdaftar untuk " . $model . '.');
        }

        return DatabaseManager::get($schema);
    }

    /**
     * Menjalankan query dan mengembalikan baris mentah hasil query.
     *
     * Urutan: table → select (ber-alias) → JOIN relasi → callback → limit → eksekusi.
     * Callback dijalankan setelah JOIN supaya alias relasi sudah tersedia, tetapi
     * JANGAN memanggil select()/table() di dalamnya karena akan mengubah daftar
     * kolom yang dipakai untuk hidrasi.
     *
     * @param ?Closure(QueryBuilder): void $query
     * @param ?int $force_limit Limit yang dipasang setelah callback (mis. 1 untuk first()).
     * @return array<int, mixed>
     */
    protected function rows(?Closure $query = null, ?int $force_limit = null): array {
        $builder = $this->builder($query);

        if($force_limit !== null) {
            $builder->limit($force_limit);
        }

        $result = $builder->get()->result();

        return is_array($result) ? $result : [];
    }

    /**
     * Menyusun QueryBuilder lengkap: table → select → JOIN relasi → callback.
     *
     * Bila $select diisi (mis. untuk COUNT), kolom model dan kolom relasi tidak
     * ikut dipasang. JOIN relasi tetap dipasang supaya filter pada kolom relasi
     * di dalam callback tetap valid.
     *
     * @param ?Closure(QueryBuilder): void $query
     * @param ?array<int, string> $select Daftar kolom SELECT; null = kolom model + relasi.
     */
    protected function builder(?Closure $query = null, ?array $select = null): QueryBuilder {
        $model = $this->model;
        $base = $model::get_table();
        $with = $this->with->toArray();

        $with_columns = $select === null;

        if($select === null) {
            $select = [];
            foreach($model::column_names() as $column) {
                $select[] = "{$base}.{$column}";
            }
        }

        $joins = [];
        $this->collect($model, $with, '', $base, $joins, $select, $with_columns);

        $builder = DB::table($base)->useConnection($this->connection($model))->select($select);

        foreach($joins as [$table, $on]) {
            $builder->leftJoin($table, $on);
        }

        if ($this->where->hasWheres()) {
            $builder->where($this->where);
        }

        if($query !== null) {
            $query($builder);
        }

        return $builder;
    }

    /**
     * Menelusuri AST `with` dan menyusun JOIN + daftar kolom SELECT.
     *
     * $parent_alias adalah alias yang dipakai sisi kiri ON: nama tabel dasar untuk
     * level pertama, lalu alias induk untuk level berikutnya.
     *
     * @param class-string<Model> $parent_model
     * @param array<int, array{relation: string, with: array}> $with
     * @param array<int, array{0: string, 1: string}> $joins
     * @param array<int, string> $select
     * @param bool $with_columns Apakah kolom relasi ikut dimasukkan ke SELECT.
     */
    protected function collect(string $parent_model, array $with, string $parent_path, string $parent_alias, array &$joins, array &$select, bool $with_columns = true): void {
        foreach($with as $node) {
            $relation = $parent_model::relation($node['relation']);
            $related = $relation->model;

            if($related::get_schema() !== $parent_model::get_schema()) {
                throw new RuntimeException(
                    "Relasi '{$node['relation']}' berada di koneksi '" . $related::get_schema()
                    . "', berbeda dari '" . $parent_model::get_schema()
                    . "'. JOIN lintas koneksi tidak didukung."
                );
            }

            $path = $parent_path === '' ? $node['relation'] : $parent_path . '__' . $node['relation'];
            $table = $related::get_table();
            $key = $relation->ownerKey ?? $related::key_column();

            $joins[] = ["{$table} AS {$path}", "{$path}.{$key} = {$parent_alias}.{$relation->foreignKey}"];

            if($with_columns) {
                foreach($related::column_names() as $column) {
                    $select[] = "{$path}.{$column} AS {$path}__{$column}";
                }
            }

            $this->collect($related, $node['with'], $path, $path, $joins, $select, $with_columns);
        }
    }

}
