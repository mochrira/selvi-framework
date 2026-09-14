<?php

namespace Selvi\Database\Builder;

use Closure;
use Selvi\Collection;
use Selvi\DB;
use Selvi\Model;

/**
 * Query untuk model: menyusun JOIN dari relasi `with()` lalu menghidrasi baris
 * hasil query menjadi objek model (termasuk property relasi).
 *
 * Alias tabel dan prefix kolom diturunkan dari path relasi, mis. `grup` dan
 * `grup__wilayah`, sehingga antar tabel tidak ada nama kolom yang bertabrakan.
 */
class ModelQuery {

    /**
     * @var class-string<Model>
     */
    private string $model;

    private WithBuilder $with;

    /**
     * @param class-string<Model> $model
     */
    public function __construct(string $model) {
        $this->model = $model;
        $this->with = new WithBuilder();
    }

    public function with(string $relation, ?Closure $nest = null): static {
        $this->with->with($relation, $nest);
        return $this;
    }

    /**
     * Mengambil semua baris sebagai Collection model.
     *
     * Callback $query dijalankan setelah tabel, kolom, dan JOIN relasi terpasang,
     * sehingga alias relasi (mis. "grup.nmGrup") bisa dipakai di dalamnya.
     *
     * @param ?Closure(QueryBuilder): void $query
     */
    public function all(?Closure $query = null): Collection {
        $model = $this->model;
        $with = $this->with->toArray();

        return Collection::fromMap(fn(mixed $item) => $model::of($item, '', $with), $this->rows($query));
    }

    /**
     * Mengambil baris pertama sebagai model, atau null bila tidak ada.
     *
     * @param ?Closure(QueryBuilder): void $query
     */
    public function first(?Closure $query = null): ?Model {
        $model = $this->model;
        $with = $this->with->toArray();
        $rows = $this->rows($query);

        if (empty($rows)) return null;

        return $model::of($rows[0], '', $with);
    }

    /**
     * Menjalankan query dan mengembalikan baris mentah hasil query.
     *
     * Urutan: table → select (ber-alias) → JOIN relasi → callback → eksekusi.
     * Callback dijalankan paling akhir supaya alias relasi sudah tersedia, tetapi
     * JANGAN memanggil select()/table() di dalamnya karena akan mengubah daftar
     * kolom yang dipakai untuk hidrasi.
     *
     * @param ?Closure(QueryBuilder): void $query
     * @return array<int, mixed>
     */
    protected function rows(?Closure $query = null): array {
        $model = $this->model;
        $base = $model::get_table();
        $with = $this->with->toArray();

        $select = [];
        foreach($model::column_names() as $column) {
            $select[] = "{$base}.{$column}";
        }

        $joins = [];
        $this->collect($model, $with, '', $base, $joins, $select);

        $builder = DB::table($base)->select($select);

        foreach($joins as [$table, $on]) {
            $builder->leftJoin($table, $on);
        }

        if($query !== null) {
            $query($builder);
        }

        $result = $builder->get()->result();

        return is_array($result) ? $result : [];
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
     */
    protected function collect(string $parent_model, array $with, string $parent_path, string $parent_alias, array &$joins, array &$select): void {
        foreach($with as $node) {
            $relation = $parent_model::relation($node['relation']);
            $related = $relation->model;

            $path = $parent_path === '' ? $node['relation'] : $parent_path . '__' . $node['relation'];
            $table = $related::get_table();
            $key = $relation->ownerKey ?? $related::key_column();

            $joins[] = ["{$table} AS {$path}", "{$path}.{$key} = {$parent_alias}.{$relation->foreignKey}"];

            foreach($related::column_names() as $column) {
                $select[] = "{$path}.{$column} AS {$path}__{$column}";
            }

            $this->collect($related, $node['with'], $path, $path, $joins, $select);
        }
    }

}
