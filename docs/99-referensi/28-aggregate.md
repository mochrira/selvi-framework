---
id: ref-aggregate
title: Selvi\Model\Attributes\Aggregate
type: referensi
class: Selvi\Model\Attributes\Aggregate
tags: [model, attribute, aggregate, relasi, group-by]
prerequisites: [model-relasi]
verified: 2026-11-01
applies_to: Selvi 3.x
---

# Referensi: `Selvi\Model\Attributes\Aggregate`

Relasi nilai agregat dari query lain, mis. jumlah anak per induk.

```php
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

## Konstruktor

### `new Aggregate($query, $key, $column, $cast, $serialize, $expression)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$query` | `string` | wajib | nama method statis yang mengembalikan query agregat |
| `$key` | `string` | wajib | kolom penghubung di hasil query agregat |
| `$column` | `string` | wajib | kolom agregat yang diambil |
| `$cast` | `?ICast` | `null` | cast nilai agregat |
| `$serialize` | `?ISerializesCast` | `null` | serializer output |
| `$expression` | `array\|string\|null` | `null` | expression SELECT; string = nama resolver, array = node `SelectNode` |

Konstruktor juga mengisi `$name = $column`.

Aturan:

- Method statis dengan nama `$query` wajib ada dan mengembalikan
  `QueryBuilder`.
- Query harus memilih kolom `$key` dan kolom agregat dengan alias `$column`,
  lalu `GROUP BY` kolom `$key`.
- `with('namaProperti')` menambahkan `LEFT JOIN (subquery) alias`.
- Alias nilai mengikuti `{query}__{column}`; pada relasi bertingkat menjadi
  `{prefix}{query}__{column}`.
- Induk tanpa baris anak menghasilkan `null`.

## Expression

`expression` membungkus kolom agregat sebelum dipetakan ke properti.

### String: resolver method (disarankan)

String adalah nama **static method public** pada model yang sama. Method
menerima context dan mengembalikan node `SelectNode`.

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

Context yang diterima resolver:

| Key | Isi |
|---|---|
| `alias` | alias hasil, `{prefix}{query}__{column}` |
| `column` | nama kolom agregat `$column` |
| `source` | alias subquery agregat, mis. `summary_kontak` |
| `prefix` | prefix relasi (string kosong pada query utama) |
| `model` | nama kelas model |

Karena `source` berasal dari context, resolver tetap benar saat relasi
bertingkat mengubah alias subquery. Node kolom tanpa `table` dikualifikasi
dengan `source`. Resolver harus `public static` dan mengembalikan array node
dengan key `type`.

### Array node

```php
#[Aggregate('summary_kontak', key: 'id_departemen', column: 'jml_kontak', expression: [
    'type' => 'function',
    'name' => 'COALESCE',
    'arguments' => [
        ['type' => 'column', 'column' => 'jml_kontak'],
        ['type' => 'literal', 'value' => 0],
    ],
])]
public int $jmlKontak = 0;
```

- Node kolom tanpa `table` otomatis memakai alias subquery agregat
  (`summary_kontak`, atau `unit_summary_kontak` saat bertingkat).
- Literal disanitasi Grammar.
- Alias output tetap `{prefix}{query}__{column}`; alias node diabaikan.
- PHP tidak mengizinkan pemanggilan method di argumen attribute, jadi gunakan
  string resolver atau array literal.

### SQL mentah

SQL mentah hanya lewat node `raw`:

```php
#[Aggregate('summary_kontak', key: 'id_departemen', column: 'jml_kontak',
    expression: ['type' => 'raw', 'sql' => 'COALESCE(summary_kontak.jml_kontak, 0)'])]
public int $jmlKontak = 0;
```

`raw` tidak disanitasi dan tidak dikualifikasi otomatis. Untuk relasi
bertingkat, alias subquery berubah sehingga resolver atau array canonical lebih
aman.

## Method instance warisan

| Method | Kembalian |
|---|---|
| `propertyName()` | `string` |
| `propertyType()` | `string` |
| `allowsNull()` | `bool` |
| `getOwner()` | `string` |
| `assign($target, $row)` | `void` — internal hydration, dipanggil `DataObject` |

## Sumber

- [src/Model/Attributes/Aggregate.php](../../src/Model/Attributes/Aggregate.php)
- [src/Database/Builder/DML/ModelQuery.php](../../src/Database/Builder/DML/ModelQuery.php) — `buildWith()`
- [src/Model/Attributes/Aggregate.php](../../src/Model/Attributes/Aggregate.php) — `assign()`
- [src/Data/DataRow.php](../../src/Data/DataRow.php) — pembacaan prefix agregat
- [tests/app/Models/Departemen.php](../../tests/app/Models/Departemen.php) — aplikasi uji

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 1 November 2026 lewat `tests/03.02-relasi`:

| Kasus | Hasil |
|---|---|
| `Departemen::with('jmlKontak')->all()` | `5` dan `null` |
| `Kontak::with('unit', ... with('departemen', ... with('jmlKontak')))` | `unit.departemen.jmlKontak` `5` |
| `DepartemenEkspresi` + array `COALESCE(jml_kontak, 0)` | `5` dan `0` |
| `DepartemenResolverEkspresi` + resolver `formatJmlKontak` | `5` dan `0` |
| Resolver agregat pada relasi bertingkat | `unit.departemen.jmlKontak` `5` |
| Relasi ke model anak dengan expression column | subquery mode terbentuk |

Belum diuji: `cast`/`serialize` pada `Aggregate`, lebih dari satu kolom agregat,
dan `query` tanpa `GROUP BY`.
