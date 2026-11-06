---
id: ref-belongs-to
title: Selvi\Model\Attributes\BelongsTo
type: referensi
class: Selvi\Model\Attributes\BelongsTo
tags: [model, attribute, relasi, belongs-to, eager-loading]
prerequisites: [model-relasi]
verified: 2026-11-01
applies_to: Selvi 3.x
---

# Referensi: `Selvi\Model\Attributes\BelongsTo`

Relasi “model ini memiliki foreign key ke model lain”.

```php
#[BelongsTo(Departemen::class, foreignKey: 'id_departemen')]
public ?Departemen $departemen;
```

## Konstruktor

### `new BelongsTo($model, $foreignKey, $ownerKey)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$model` | `class-string<Model>` | wajib | kelas model tujuan |
| `$foreignKey` | `string` | wajib | kolom foreign key di tabel model ini |
| `$ownerKey` | `?string` | `null` | kolom key di model tujuan; **belum dipakai** `ModelQuery` saat ini |

Aturan:

- Relasi hanya dimuat bila diminta lewat `with('namaProperti')`.
- `ModelQuery` membuat `LEFT JOIN` ke tabel tujuan. Alias kolom hasil join
  berbentuk `{relasi}__{kolom}` (relasi bertingkat:
  `{induk}_{relasi}__{kolom}`).
- Join selalu memakai kolom `key` model tujuan; `$ownerKey` baru disimpan di
  attribute dan belum memengaruhi SQL.
- Properti relasi yang tidak diminta tetap `null` (atau uninitialized bila
  tidak nullable).
- Nama relasi = nama properti, bukan nama tabel.

## Method instance warisan

| Method | Kembalian |
|---|---|
| `propertyName()` | `string` |
| `propertyType()` | `string` — kelas model tujuan |
| `allowsNull()` | `bool` |
| `getOwner()` | `string` |
| `assign($target, $row)` | `void` — internal hydration, dipanggil `DataObject` |

## Sumber

- [src/Model/Attributes/BelongsTo.php](../../src/Model/Attributes/BelongsTo.php)
- [src/Model/PropertyAttribute.php](../../src/Model/PropertyAttribute.php)
- [src/Database/Builder/DML/ModelQuery.php](../../src/Database/Builder/DML/ModelQuery.php) — `buildWith()`
- [src/Model/Attributes/BelongsTo.php](../../src/Model/Attributes/BelongsTo.php) — `assign()`
- [src/Data/DataRow.php](../../src/Data/DataRow.php) — pembacaan prefix relasi
- [tests/app/Models/Kontak.php](../../tests/app/Models/Kontak.php) — aplikasi uji

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 1 November 2026 lewat `tests/03.02-relasi`:

| Kasus | Hasil |
|---|---|
| `with('departemen')` | relasi terisi |
| `with('unit')` | relasi terisi |
| tanpa `with()` | relasi `null` |
| `with('unit', ... with('departemen'))` | relasi dua tingkat terisi |
| `relations()` | `departemen`, `unit`, `profesi` |
| `relation('departemen')` | instance `BelongsTo` |

Belum diuji: `ownerKey` kustom (parameter belum dipakai `ModelQuery`) dan
`foreignKey` yang bukan kolom key.
