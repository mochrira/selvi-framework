---
id: ref-data-object
title: Selvi\Data\DataObject
type: referensi
class: Selvi\Data\DataObject
tags: [dto, dataobject, mapping, toarray, attribute]
prerequisites: [data-object]
verified: 2026-11-01
applies_to: Selvi 3.x
---

# Referensi: `Selvi\Data\DataObject`

Basis DTO berbasis attribute. Kelas ini memetakan objek sumber (baris query,
`stdClass`) ke properti bertipe dan mengembalikan array lewat `toArray()`.

Aturan yang berlaku:

- Hanya properti yang punya attribute turunan
  `Selvi\Interfaces\IPropertyAttribute` yang dipetakan. Properti lain diabaikan.
- Daftar properti di-cache per kelas di `self::$cache_properties`, bukan per
  instance.
- `of()` memakai `new static()`, sehingga kelas turunan (termasuk
  `Selvi\Model` dan DTO aplikasi) memakai aturan yang sama.
- Kelas ini mengimplementasikan `Selvi\Interfaces\IArrayable` dan
  `Selvi\Interfaces\IDataObject`.

Pemakaian ada di [Data Object](../02-dto/01-data-object.md),
[properti bersarang](../02-dto/02-properti-nested.md), dan
[cast dan serialize](../02-dto/03-cast-dan-serialize.md).

## Method statis

### `DataObject::of($item, $prefix)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$item` | `object` | wajib | sumber data |
| `$prefix` | `?string` | `''` | prefix jalur untuk properti bersarang; dipakai saat rekursi |

**Kembalian:** `static` — instance kelas yang memanggil.

Perilaku per properti:

1. Properti dengan `prefix` dan tipe turunan `DataObject`: dicari kunci
   berawalan `{$prefix}{$property->prefix}_`; bila ada, kelas properti dipanggil
   dengan `of($item, "{$prefix}{$property->prefix}_")`.
2. Properti lain: kunci sumber `{name}`, atau `{prefix}_{name}` saat rekursi.
3. Kunci tidak ditemukan dan properti nullable → diisi `null`.
4. Kunci tidak ditemukan dan properti non-nullable → dibiarkan tidak
   terinisialisasi.
5. Nilai ditemukan → `Converter::get()`, lalu bila tipe properti turunan
   `DataObject`, nilainya diubah lewat `of()` pada kelas tersebut.

## Method instance

### `DataObject::toArray()`

**Kembalian:** `array<string, mixed>` — kunci berupa nama properti.

Perilaku:

- Properti tidak terinisialisasi: dilewati bila `optional` bernilai `true`,
  selain itu bernilai `null`.
- Nilai `IArrayable` → `toArray()` milik nilai tersebut.
- Nilai lain → `Converter::serialize()` dengan `cast` dan `serialize` properti.

## Sumber

- `src/Data/DataObject.php`
- `src/Interfaces/IDataObject.php`
- `src/Interfaces/IArrayable.php`
- `src/Data/DataProperty.php` — attribute yang dibaca
- `tests/02.01-data-object/index.php`, `tests/02.02-properti-nested/index.php`,
  dan `tests/02.03-cast-dan-serialize/index.php` — aplikasi uji

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 1 November 2026 lewat tiga aplikasi uji bab DTO (PHP 8.4.14):

| Kasus | Hasil |
|---|---|
| `of()` dengan sumber `stdClass` | properti terisi sesuai attribute |
| `of()` dengan kunci hilang | nullable menjadi `null`, non-nullable tetap tidak terinisialisasi |
| `toArray()` dengan `optional: true` | kunci dihilangkan |
| `of()` + `prefix` satu dan dua tingkat | properti bersarang terbentuk |
| `toArray()` dengan `cast` dan `serialize` | nilai mengikuti cast/serializer |

Belum diuji: cache `properties()` pada kelas yang sama dengan instance berbeda,
dan properti `readonly`/`static`.
