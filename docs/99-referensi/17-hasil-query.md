---
id: ref-hasil-query
title: Selvi\Database\Interfaces\IResult
type: referensi
class: Selvi\Database\Interfaces\IResult
tags: [database, result, mysqliresult, rows, row, num-rows]
prerequisites: [database-membaca-data]
verified: 2026-11-01
applies_to: Selvi 3.x
---

# Referensi: `Selvi\Database\Interfaces\IResult`

Kontrak hasil query. Implementasi MySQL adalah
`Selvi\Database\Drivers\MySQL\MySQLResult`, yang membungkus `mysqli_result`
atau `bool`.

Aturan yang berlaku:

- `row()` dan `rows()` memanggil `data_seek(0)` lebih dulu, sehingga bisa
  dipanggil berulang dan selalu membaca dari awal.
- Untuk statement yang tidak menghasilkan result set, `IConnection::query()`
  mengembalikan `bool`; bila `MySQLResult` tetap dibuat dengan nilai `bool`,
  `num_rows()`, `rows()`, dan `row()` mengembalikan `bool` tersebut.
- Baris selalu `stdClass`; nilai kolom mengikuti driver (`string` untuk MySQL).

Pemakaian ada di [membaca data](../04-database/03-membaca-data.md).

## Method instance

### `IResult::num_rows()`

**Kembalian:** `int|bool` — jumlah baris, atau `bool` untuk hasil non-SELECT.

### `IResult::rows()`

**Kembalian:** `array|bool|null` — `list<stdClass>`, `bool` untuk hasil
non-SELECT, atau `null` bila isi result tidak dikenali.

### `IResult::row()`

**Kembalian:** `stdClass|bool|null` — baris pertama, `null` bila tidak ada
baris, atau `bool` untuk hasil non-SELECT.

### `IResult::result()`

**Deprecated.** Alias `rows()`. Gunakan `rows()`.

**Kembalian:** `array|bool|null`.

### `IResult::__construct($result)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$result` | `mixed` | wajib | `mysqli_result` atau `bool` |

Dipakai internal oleh `MySQLConnection::query()`.

## Sumber

- [src/Database/Interfaces/IResult.php](../../src/Database/Interfaces/IResult.php)
- [src/Database/Drivers/MySQL/MySQLResult.php](../../src/Database/Drivers/MySQL/MySQLResult.php)
- [src/Database/Drivers/MySQL/MySQLConnection.php](../../src/Database/Drivers/MySQL/MySQLConnection.php) — pembuatan result
- [tests/04.03-membaca-data/index.php](../../tests/04.03-membaca-data/index.php) — aplikasi uji

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 1 November 2026 (PHP 8.4.14, MariaDB 10.7.8):

| Kasus | Hasil |
|---|---|
| `GET /04.03-membaca-data/row` | `num_rows()` `1`; `row()` satu `stdClass` |
| `GET /04.03-membaca-data/row-kosong` | `num_rows()` `0`; `row()` `null` |
| `GET /04.03-membaca-data/` | `rows()` tiga `stdClass` |
| `GET /04.03-membaca-data/hasil-bool` | `query('SET ...')` mengembalikan `bool` `true` |

Belum diuji: `rows()`/`row()` pada hasil `false` (query non-SELECT yang gagal)
dan `result()` (deprecated).
