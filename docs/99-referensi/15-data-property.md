---
id: ref-data-property
title: Selvi\Data\DataProperty
type: referensi
class: Selvi\Data\DataProperty
tags: [dto, dataproperty, attribute, cast, serialize, optional, prefix]
prerequisites: [data-object]
verified: 2026-11-01
applies_to: Selvi 3.x
---

# Referensi: `Selvi\Data\DataProperty`

Attribute penanda properti DTO. Kelas ini mengimplementasikan
`Selvi\Data\Interfaces\IDataProperty`. `Selvi\Model\PropertyAttribute`
memperluas kelas ini, sehingga attribute model seperti `Column`, `BelongsTo`,
`Aggregate`, dan `NotMapped` mewarisi metadata serta `assign()` yang sama.

Aturan yang berlaku:

- Hanya boleh dipasang pada properti (`Attribute::TARGET_PROPERTY`).
- `name` adalah kunci sumber, sedangkan kunci output selalu nama properti.
- `name` boleh kosong bila `prefix` diisi.
- Nilai `cast` menang atas cast bawaan tipe. Nilai `serialize` menang atas
  cast saat `toArray()`.

Pemakaian ada di [Data Object](../02-dto/01-data-object.md),
[properti bersarang](../02-dto/02-properti-nested.md), dan
[cast dan serialize](../02-dto/03-cast-dan-serialize.md).

## Konstruktor

### `new DataProperty($name, $prefix, $optional, $cast, $serialize)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | `''` | kunci sumber; kosong berarti hanya memakai `prefix` |
| `$prefix` | `?string` | `null` | prefix jalur untuk properti bersarang bertipe `DataObject` |
| `$optional` | `?bool` | `false` | bila `true`, properti tidak terinisialisasi dilewati `toArray()` |
| `$cast` | `?ICast` | `null` | cast dua arah pengganti cast bawaan |
| `$serialize` | `?ISerializesCast` | `null` | serializer khusus output `toArray()` |

Contoh:

```php
#[DataProperty('nm_kontak')]
public ?string $nmKontak;

#[DataProperty(prefix: 'departemen')]
public ?DepartemenDTO $departemen;

#[DataProperty('opsi', cast: new ArrayCast())]
public ?array $opsi;
```

## Method instance

### `DataProperty::propertyName()`

**Kembalian:** `string` — nama properti PHP. Diisi otomatis oleh
`DataObject` saat membaca attribute; tidak perlu diatur manual.

### `DataProperty::propertyType()`

**Kembalian:** `string` — nama tipe properti hasil `ReflectionType::getName()`.
Diisi otomatis oleh `DataObject`.

### `DataProperty::allowsNull()`

**Kembalian:** `bool` — `true` bila properti bertipe nullable.

### `DataProperty::assign($target, $row)`

Method internal yang dipanggil `DataObject::of()`. Menerima objek target dan
`Selvi\Data\DataRow`; implementasi generiknya berada langsung di
`DataProperty` dan mendukung `prefix` milik properti bersarang.

Method ini tidak perlu dipanggil manual.

### `DataProperty::setPropertyName($name)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | wajib | nama properti PHP |

**Kembalian:** `void`. Dipanggil internal oleh `DataObject`.

### `DataProperty::setPropertyType($type, $allowsNull)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$type` | `string` | wajib | nama tipe properti |
| `$allowsNull` | `bool` | wajib | status nullable properti |

**Kembalian:** `void`. Dipanggil internal oleh `DataObject`.

## Sumber

- [src/Data/DataProperty.php](../../src/Data/DataProperty.php)
- [src/Model/PropertyAttribute.php](../../src/Model/PropertyAttribute.php) — attribute dasar model yang memperluas
  `DataProperty`
- [src/Data/DataRow.php](../../src/Data/DataRow.php) — sumber data dan prefix
- [src/Data/Interfaces/IDataProperty.php](../../src/Data/Interfaces/IDataProperty.php) — kontrak attribute properti
- [src/Database/Interfaces/ICast.php](../../src/Database/Interfaces/ICast.php) dan
  [src/Database/Interfaces/ISerializesCast.php](../../src/Database/Interfaces/ISerializesCast.php) — tipe `cast` dan `serialize`
- [tests/02.01-data-object/index.php](../../tests/02.01-data-object/index.php), [tests/02.02-properti-nested/index.php](../../tests/02.02-properti-nested/index.php),
  dan [tests/02.03-cast-dan-serialize/index.php](../../tests/02.03-cast-dan-serialize/index.php) — aplikasi uji

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 1 November 2026 lewat aplikasi uji bab DTO (PHP 8.4.14):

| Kasus | Hasil |
|---|---|
| `name` saja | properti terisi dari kunci `name` |
| `prefix` saja (`name` kosong) | properti bersarang terbentuk |
| `optional: true` | kunci hilang dari `toArray()` saat properti tidak terinisialisasi |
| `cast` dan `serialize` | nilai mengikuti cast/serializer |

`DataProperty` tidak memiliki `getOwner()`/`setOwner()`; ia hanya
mengimplementasikan kontrak `IDataProperty`.

Semua contoh di halaman ini teruji.
