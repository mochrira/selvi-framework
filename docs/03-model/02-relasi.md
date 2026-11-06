---
id: model-relasi
title: Relasi
type: panduan
tags: [model, relasi, belongs-to, aggregate, eager-loading, join]
prerequisites: [model]
verified: 2026-11-01
applies_to: Selvi 3.x
---

# Relasi

Model memuat relasi secara eksplisit lewat `with()`. Tidak ada lazy loading:
relasi yang tidak diminta tetap `null`.

Ada dua jenis relasi:

- **`BelongsTo`** — model ini memiliki foreign key ke model lain.
- **`Aggregate`** — nilai agregat dari query lain (mis. jumlah kontak per
  departemen).

## Jawaban singkat

```php
#[BelongsTo(Departemen::class, foreignKey: 'id_departemen')]
public ?Departemen $departemen;

$kontak = Kontak::with('departemen')->find(1);
$kontak?->departemen?->nmDepartemen;
```

Tanpa `with()`, properti relasi tetap `null`; tidak ada lazy loading.

## Prasyarat

- [Model](01-model.md) sudah dipahami.
- Tabel relasi tersedia.

## 1. Mendefinisikan `BelongsTo`

```php
use Selvi\Model\Attributes\BelongsTo;

#[BelongsTo(Departemen::class, foreignKey: 'id_departemen')]
public ?Departemen $departemen;

#[BelongsTo(Unit::class, foreignKey: 'id_unit')]
public ?Unit $unit;

#[BelongsTo(Profesi::class, foreignKey: 'id_profesi')]
public ?Profesi $profesi;
```

Parameter:

| Parameter | Arti |
|---|---|
| `$model` | kelas model tujuan |
| `$foreignKey` | kolom foreign key di tabel model ini |
| `$ownerKey` | opsional; disimpan tetapi belum dipakai `ModelQuery` (join selalu memakai kolom `key` model tujuan) |

## 2. Memuat relasi dengan `with()`

```php
$kontak = Kontak::with('departemen')
    ->with('unit')
    ->find(5);

return jsonResponse($kontak?->toArray());
```

Hasil:

```json
{
    "idKontak": 5,
    "nmKontak": "John Doe",
    "idDepartemen": 1,
    "idUnit": 1,
    "idProfesi": 1,
    "createdAt": "2026-10-21 04:46:48",
    "departemen": {
        "idDepartemen": 1,
        "nmDepartemen": "Programmer",
        "jmlKontak": null
    },
    "unit": {
        "idUnit": 1,
        "nmUnit": "Manager",
        "idDepartemen": 1,
        "departemen": null
    },
    "profesi": null
}
```

`profesi` tetap `null` karena tidak diminta. `unit.departemen` juga `null` untuk
alasan yang sama.

Nama relasi adalah **nama properti** yang diberi attribute, bukan nama tabel.

## 3. Relasi bertingkat

```php
$kontak = Kontak::with('unit', fn($builder) => $builder
    ->with('departemen'))
    ->find(5);
```

Hasil `unit.departemen`:

```json
{
    "unit": {
        "idUnit": 1,
        "nmUnit": "Manager",
        "idDepartemen": 1,
        "departemen": {
            "idDepartemen": 1,
            "nmDepartemen": "Programmer",
            "jmlKontak": null
        }
    }
}
```

Closure pada `with()` menerima `WithBuilder` dan bisa memanggil `with()` lagi.

### Model anak dengan expression

Jika model anak punya `Column` dengan `expression`, relasinya dipaksa memakai
subquery. Resolver memakai `source` dari context, sehingga `unit` tetap benar:

```php
use Selvi\Database\Builder\DML\Clauses\SelectNode;

#[Table('unit', 'main')]
class UnitResolverEkspresi extends Model {

    #[Column('id_unit', key: true)]
    public int $idUnit;

    #[Column('nm_unit', expression: 'namaUnit')]
    public ?string $nmUnit;

    public static function namaUnit(array $context): array {
        return SelectNode::function('COALESCE', [
            SelectNode::column($context['column'], $context['source']),
            SelectNode::literal('Tanpa Unit'),
        ]);
    }

}
```

```sql
LEFT JOIN (
    SELECT unit.id_unit AS unit__id_unit,
           COALESCE(unit.nm_unit, 'Tanpa Unit') AS unit__nm_unit
    FROM unit
) unit ON unit.unit__id_unit = kontak.id_unit
```

Hasil `KontakUnitResolverEkspresi::with('unit')->find(5)` memuat
`"unit": {"idUnit": 1, "nmUnit": "Manager"}`. Ini memastikan fungsi tidak
dievaluasi ulang di query luar.

## 4. `Aggregate`

`Aggregate` mengambil satu nilai dari query agregat. Definisinya terdiri dari
attribute dan method statis dengan nama yang sama:

```php
use Selvi\Database\Builder\DML\QueryBuilder;
use Selvi\Database\DB;
use Selvi\Model\Attributes\Aggregate;

#[Aggregate('summary_kontak', key: 'id_departemen', column: 'jml_kontak')]
public ?int $jmlKontak = null;

public static function summary_kontak(): QueryBuilder {
    return DB::table('kontak')
        ->select(
            'kontak.id_departemen',
            ['COUNT(kontak.id_kontak)', 'jml_kontak'])
        ->group('kontak.id_departemen');
}
```

```php
$data = Departemen::with('jmlKontak')->all()->toArray();
```

Hasil:

```json
{
    "data": [
        { "idDepartemen": 1, "nmDepartemen": "Programmer", "jmlKontak": 5 },
        { "idDepartemen": 2, "nmDepartemen": "Programmer", "jmlKontak": null }
    ]
}
```

Kontrak query agregat:

- Method statis harus bernama sama dengan parameter `query`.
- Hasil `SELECT` harus memuat kolom `key` dan kolom agregat dengan alias sama
  dengan parameter `column`.
- Query harus `GROUP BY` kolom `key`.

Departemen 2 tidak memiliki kontak, sehingga LEFT JOIN menghasilkan `null`.

### Aggregate dengan expression

```php
use Selvi\Database\Builder\DML\Clauses\SelectNode;

#[Aggregate('summary_kontak', key: 'id_departemen', column: 'jml_kontak',
    expression: 'formatJmlKontak')]
public int $jmlKontak = 0;

public static function formatJmlKontak(array $context): array {
    return SelectNode::function('COALESCE', [
        SelectNode::column($context['column'], $context['source']),
        SelectNode::literal(0),
    ]);
}
```

Hasil:

```json
{
    "data": [
        { "idDepartemen": 1, "nmDepartemen": "Programmer", "jmlKontak": 5 },
        { "idDepartemen": 2, "nmDepartemen": "Programmer", "jmlKontak": 0 }
    ]
}
```

Resolver menerima `source` berisi alias subquery agregat (`summary_kontak`,
atau `unit_summary_kontak` saat bertingkat), sehingga tetap benar tanpa
menulis alias manual. Bentuk array node juga didukung; SQL mentah hanya lewat
node `['type' => 'raw', ...]`.

## 5. Agregat di dalam relasi

```php
$kontak = Kontak::with('unit', fn($builder) => $builder
    ->with('departemen', fn($inner) => $inner->with('jmlKontak')))
    ->find(5);
```

Hasil `unit.departemen.jmlKontak` bernilai `5`.

## 6. Melihat daftar relasi

```php
Kontak::relations();            // ['departemen' => BelongsTo, 'unit' => ..., 'profesi' => ...]
Kontak::relation('departemen'); // BelongsTo
Kontak::relation('tidakAda');   // null
```

`relations()` mengembalikan semua properti bertipe `BelongsTo` atau `Aggregate`.

## Referensi API

- [`Model`](../99-referensi/21-model.md) — `with()`, `relations()`,
  `relation()`.
- [`ModelQuery`](../99-referensi/22-model-query.md) — `with()`.
- [`BelongsTo`](../99-referensi/27-belongs-to.md) dan
  [`Aggregate`](../99-referensi/28-aggregate.md).
- [`QueryBuilder`](../99-referensi/16-query-builder.md) — query untuk agregat.

## Kesalahan umum

| Gejala | Penyebab | Perbaikan |
|---|---|---|
| Relasi selalu `null` | tidak memakai `with()` | tambahkan `with('namaRelasi')` |
| `Call to undefined method ...::nama_relasi()` | method agregat tidak ada | buat method statis dengan nama sama seperti `query` |
| `jmlKontak` selalu `null` | alias kolom agregat tidak cocok dengan `column` | samakan alias `SELECT` dengan parameter `column` |
| Relasi bertingkat `null` | closure tidak memanggil `with()` di level anak | tambahkan `with()` pada closure |
| `Unknown column` | `foreignKey`/`key` salah | samakan dengan nama kolom sebenarnya |

## Sumber

- `src/Model.php` — `with()`, `relations()`, `relation()`
- `src/Database/Builder/DML/ModelQuery.php` — `buildWith()`, alias join
- `src/Model/Attributes/BelongsTo.php` dan `Aggregate.php`
- `tests/app/Models/` — contoh model
- `tests/03.02-relasi/index.php` — aplikasi uji halaman ini

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 1 November 2026 lewat `http://localhost:8089/03.02-relasi/`
(MariaDB 10.7.8, PHP 8.4.14):

| Endpoint | Hasil |
|---|---|
| `GET /03.02-relasi/` | `200`, `departemen` dan `unit` terisi; `profesi` `null` |
| `GET /03.02-relasi/aggregate` | `200`, `jmlKontak` `5` dan `null` |
| `GET /03.02-relasi/nested` | `200`, `unit.departemen` terisi |
| `GET /03.02-relasi/nested-aggregate` | `200`, `unit.departemen.jmlKontak` `5` |
| `GET /03.02-relasi/relations` | `200`, tiga nama relasi; `BelongsTo`; `tidakAda` `null` |
| `GET /03.02-relasi/tanpa-relasi` | `200`, semua relasi `null` |
| `GET /03.02-relasi/aggregate-ekspresi` | `200`, array expression, `jmlKontak` `5` dan `0` |
| `GET /03.02-relasi/aggregate-resolver` | `200`, resolver expression, `jmlKontak` `5` dan `0` |
| `GET /03.02-relasi/nested-ekspresi` | `200`, array expression anak, subquery mode |
| `GET /03.02-relasi/nested-resolver` | `200`, resolver expression anak, subquery mode |
| `GET /03.02-relasi/nested-aggregate-resolver` | `200`, resolver agregat bertingkat, `5` |

Belum diuji: `ownerKey` (parameter belum dipakai `ModelQuery`), `Aggregate`
dengan beberapa kolom, dan relasi bertingkat lebih dari dua level.
