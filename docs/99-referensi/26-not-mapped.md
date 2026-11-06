---
id: ref-not-mapped
title: Selvi\Model\Attributes\NotMapped
type: referensi
class: Selvi\Model\Attributes\NotMapped
tags: [model, attribute, notmapped, properti, serialisasi]
prerequisites: [model]
verified: 2026-11-01
applies_to: Selvi 3.x
---

# Referensi: `Selvi\Model\Attributes\NotMapped`

Menandai properti yang **tidak** dipetakan ke kolom database.

```php
#[NotMapped]
public ?string $catatan;
```

Aturan:

- Tidak punya parameter konstruktor.
- Tidak masuk `Model::columns()` maupun `Model::relations()`.
- Tidak ikut `toArrayDb()`, `create()`, dan `update()`.
- Tetap terdaftar di `Model::properties()` dan tetap diserialisasi
  `toArray()` bila propertinya diisi.
- `setPropertyName()` mengisi `name` dengan nama properti, sehingga pemetaan
  generik `DataObject::of()` memakai nama properti sebagai kunci sumber bila
  kunci itu ada.

## Method instance

### `NotMapped::setPropertyName($name)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | wajib | nama properti PHP |

**Kembalian:** `void`. Memanggil implementasi induk lalu mengisi `name`.

## Sumber

- [src/Model/Attributes/NotMapped.php](../../src/Model/Attributes/NotMapped.php)
- [src/Model/PropertyAttribute.php](../../src/Model/PropertyAttribute.php)
- [src/Model.php](../../src/Model.php) — `columns()`
- [tests/03.01-model/index.php](../../tests/03.01-model/index.php) — aplikasi uji

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 1 November 2026 lewat `GET /03.01-model/notmapped`:

```json
{
    "toArray": {
        "idKontak": 5,
        "nmKontak": "John Doe",
        "catatan": "hanya di memori"
    },
    "toArrayDb": {
        "id_kontak": 5,
        "nm_kontak": "John Doe"
    }
}
```

Belum diuji: `NotMapped` dengan nilai `null`/tidak diisi.
