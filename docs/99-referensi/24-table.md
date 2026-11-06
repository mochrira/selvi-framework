---
id: ref-table
title: Selvi\Model\Attributes\Table
type: referensi
class: Selvi\Model\Attributes\Table
tags: [model, attribute, table, schema, koneksi]
prerequisites: [model]
verified: 2026-11-01
applies_to: Selvi 3.x
---

# Referensi: `Selvi\Model\Attributes\Table`

Attribute level kelas yang menentukan tabel dan koneksi model.

```php
#[Table('kontak', schema: 'main')]
class Kontak extends Model { }
```

## Konstruktor

### `new Table($name, $schema)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | wajib | nama tabel |
| `$schema` | `string` | wajib | nama koneksi di `Manager`, bukan nama database |

**Kembalian:** instance attribute.

Aturan:

- Wajib ada; `Model::getTable()`/`getSchema()` melempar
  `InvalidArgumentException: Kelas ... belum punya atribute Table` bila tidak.
- Bisa dipasang tanpa tanda kurung argumen posisional; gunakan named argument
  `schema:` agar jelas.
- `setOwner()`/`getOwner()` diwarisi dari `Selvi\Model\Base\Attribute` dan diisi
  otomatis oleh `Model::attributes()`.

## Sumber

- [src/Model/Attributes/Table.php](../../src/Model/Attributes/Table.php)
- [src/Model/Attribute.php](../../src/Model/Attribute.php)
- [src/Model.php](../../src/Model.php) — `getTable()`, `getSchema()`
- [tests/app/Models/Kontak.php](../../tests/app/Models/Kontak.php) — aplikasi uji

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 1 November 2026 lewat `tests/03.01-model` dan `tests/03.02-relasi`:
`Table('kontak', schema: 'main')` membuat `Model::getTable()` mengembalikan
`kontak` dan query memakai koneksi `main`.

Belum diuji: model tanpa attribute `Table`.
