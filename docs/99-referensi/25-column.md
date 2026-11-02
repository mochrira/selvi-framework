---
id: ref-column
title: Selvi\Model\Attributes\Column
type: referensi
class: Selvi\Model\Attributes\Column
tags: [model, attribute, column, cast, serialize, key]
prerequisites: [model]
verified: 2026-11-01
applies_to: Selvi 3.x
---

# Referensi: `Selvi\Model\Attributes\Column`

Attribute properti yang memetakan properti model ke kolom database.

```php
#[Column('id_kontak', key: true)]
public int $idKontak;

#[Column('created_at', cast: new DateTimeCast(), serialize: new DateTimeCast(format: DATE_ATOM))]
public ?DateTime $createdAt;
```

## Konstruktor

### `new Column($name, $key, $cast, $serialize, $expression)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | wajib | nama kolom database |
| `$key` | `bool` | `false` | menandai primary key |
| `$cast` | `?ICast` | `null` | cast dua arah (baca/tulis) |
| `$serialize` | `?ISerializesCast` | `null` | serializer khusus `toArray()` |
| `$expression` | `array\|string\|null` | `null` | expression SELECT; string = nama resolver, array = node `SelectNode` |

Aturan:

- Hanya properti bertanda `Column` yang masuk `Model::columns()`,
  `toArrayDb()`, `create()`, dan `update()`.
- Satu model harus punya minimal satu `key: true` agar `find()`, `update()`, dan
  `delete()` bekerja.
- `cast`/`serialize` menang atas cast bawaan tipe properti. Detail di
  [`Converter`](29-converter.md).
- Metadata properti (`propertyName()`, `propertyType()`, `allowsNull()`) diisi
  otomatis oleh `Model::properties()`.

## Expression

`expression` hanya memengaruhi `SELECT`. Kolom fisik `$name` tetap dipakai
`create()`, `update()`, `delete()`, `toArrayDb()`, dan `WHERE` key.

### String: resolver method

String adalah nama **static method public** pada model yang sama. Method
menerima context dan mengembalikan node `SelectNode`.

```php
use Selvi\Database\Builder\DML\Clauses\SelectNode;

#[Column('nm_kontak', expression: 'namaKontak')]
public ?string $nmKontak;

public static function namaKontak(array $context): array {
    return SelectNode::function('COALESCE', [
        SelectNode::column($context['column'], $context['source']),
        SelectNode::literal('Tanpa Nama'),
    ]);
}
```

Context yang diterima resolver:

| Key | Isi |
|---|---|
| `alias` | alias hasil; root `{name}`, relasi bertingkat `{prefix}_{name}` |
| `column` | nama kolom fisik `$name` |
| `source` | tabel model pada query ini |
| `prefix` | prefix relasi (string kosong pada query utama) |
| `model` | nama kelas model |

Resolver harus `public static`, dan nilai kembaliannya harus array node dengan
key `type`. Node kolom tanpa `table` dikualifikasi dengan `source`, dan alias
dari resolver ditimpa nilai `alias` pada context.

Contoh lengkap ada di
[Model › Kolom dengan fungsi SQL](../03-model/01-model.md#7-kolom-terhitung).

### Array node

```php
#[Column('nm_kontak', expression: [
    'type' => 'function',
    'name' => 'COALESCE',
    'arguments' => [
        ['type' => 'column', 'column' => 'nm_kontak'],
        ['type' => 'literal', 'value' => 'Tanpa Nama'],
    ],
])]
public ?string $nmKontak;
```

Node kolom tanpa `table` otomatis dikualifikasi dengan tabel model. Literal
disanitasi Grammar.

### SQL mentah

SQL mentah hanya lewat node `raw`, bukan string:

```php
#[Column('nm_kontak', expression: [
    'type' => 'raw',
    'sql' => "COALESCE(kontak.nm_kontak, 'Tanpa Nama')",
])]
public ?string $nmKontak;
```

`raw` tidak disanitasi dan tidak dikualifikasi otomatis; tulis nama tabel
sendiri. Gunakan hanya untuk SQL tepercaya.

### Aturan

- Alias hasil expression selalu `alias` dari context; alias pada node diabaikan.
- `expression` pada `key: true` melempar
  `InvalidArgumentException: Kolom key tidak boleh memakai expression.`
- Resolver yang tidak ada melempar
  `InvalidArgumentException: Resolver '{nama}' tidak ditemukan pada {model}.`
- Resolver non-static/non-public atau yang tidak mengembalikan node melempar
  `InvalidArgumentException`.
- PHP tidak mengizinkan pemanggilan method di argumen attribute. Karena itu
  gunakan string resolver atau array literal, bukan `SelectNode::function()`
  langsung di `#[Column]`.
- `cast`/`serialize` tetap berlaku pada nilai hasil expression.

## Method instance warisan

| Method | Kembalian |
|---|---|
| `propertyName()` | `string` — nama properti PHP |
| `propertyType()` | `string` — nama tipe properti |
| `allowsNull()` | `bool` |
| `setPropertyName($name)` | `void` (internal) |
| `setPropertyType($type, $allowsNull)` | `void` (internal) |
| `getOwner()`/`setOwner($owner)` | `string`/`void` (internal) |

## Sumber

- `src/Model/Attributes/Column.php`
- `src/Model/PropertyAttribute.php`
- `src/Model.php` — `columns()`, `getKeyColumn()`, `toArrayDb()`
- `tests/app/Models/Kontak.php` — aplikasi uji

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 1 November 2026:

| Kasus | Hasil |
|---|---|
| `Column` biasa | `find()`, `toArray()`, `toArrayDb()`, `create()`, `update()` |
| expression resolver `namaKontak` | SQL `COALESCE(kontak.nm_kontak, 'Tanpa Nama') AS nm_kontak` |
| expression array `COALESCE` | SQL dan nilai sama, kualifikasi otomatis |
| expression array `raw` | SQL mentah dipakai apa adanya |
| resolver tidak ada / tidak static / hasil bukan node / tipe node salah | `InvalidArgumentException` |
| `toArrayDb()` dengan expression | tetap memakai kolom `nm_kontak` |
| `key: true` + expression | `InvalidArgumentException` |

Belum diuji: model tanpa kolom key, `cast` kustom pada `Column`, dan resolver
yang memakai `prefix` pada relasi bertingkat.
