---
id: docs-index
title: Dokumentasi Selvi Framework
type: indeks
tags: [selvi, dokumentasi, indeks, peta-dokumentasi]
applies_to: Selvi 3.x
---

# Dokumentasi Selvi Framework

Knowledge base untuk developer dan agent AI yang bekerja dengan Selvi Framework.
Dokumentasi ini ditulis sebagai Markdown biasa, tanpa generator situs.

## Mulai di sini

Bagian `01-memulai/` ditulis bertahap dengan urutan:

1. [Instalasi](01-memulai/01-instalasi.md) — selesai.
2. [Response](01-memulai/02-response.md) — selesai.
3. [Request](01-memulai/03-request.md) — selesai.
4. [Controller](01-memulai/04-controller.md) — selesai.

Halaman yang belum ditulis tidak ditautkan, agar tidak ada link mati.

## Database

Bab `02-database/` ditulis bertahap, dimulai dari menyiapkan koneksi:

1. [Koneksi](02-database/01-koneksi.md) — selesai.
2. [Skema tabel](02-database/02-skema-tabel.md) — selesai.

Halaman berikutnya belum ditulis: migrasi, seeder, membaca data, menulis data,
model, relasi, dan konversi value.

## Referensi API

Rujukan per kelas, satu file per kelas.

Kelas yang dipakai di halaman `01-memulai/`:

1. [`Framework::run()`](99-referensi/01-framework.md) — titik masuk aplikasi.
2. [`Selvi\Routing\Route`](99-referensi/02-route.md) — pendaftaran route, grup, dan middleware.
3. [`Selvi\Input\Request`](99-referensi/03-request.md) — pembaca data request.
4. [`Selvi\Output\Response`](99-referensi/04-response.md) — respons teks dan cookie.
5. [`Selvi\Output\JsonResponse`](99-referensi/05-json-response.md) — respons JSON.
6. [`Selvi\View`](99-referensi/06-view.md) — perender template HTML.
7. [`Selvi\Controller`](99-referensi/07-controller.md) — base class controller opsional.

Kelas yang dipakai di bab `02-database/`:

8. [`Selvi\Database\Manager`](99-referensi/08-manager.md) — registry koneksi.
9. [`Selvi\Database\DB`](99-referensi/09-db.md) — facade pembuat builder.
10. [`Selvi\Database\Builder\DDL\SchemaBuilder`](99-referensi/10-schema-builder.md) — operasi DDL.
11. [`Selvi\Database\Builder\DDL\Blueprint`](99-referensi/11-blueprint.md) — definisi `CREATE TABLE`.
12. [`Selvi\Database\Builder\DDL\AlterBlueprint`](99-referensi/12-alter-blueprint.md) — operasi `ALTER TABLE`.
13. [`Selvi\Database\Builder\DDL\Clauses\ColumnClause`](99-referensi/13-column-clause.md) — modifier kolom.

## Status penulisan

| Folder | Status |
|---|---|
| `01-memulai/` | selesai (4 halaman) |
| `02-database/` | sedang ditulis (2 halaman) |
| `99-referensi/` | selesai (13 halaman) |
| bagian lain | belum ditulis |

## Cara membaca halaman

Setiap halaman memakai bagian berikut secara konsisten:

- **Tujuan** — masalah yang diselesaikan halaman.
- **Prasyarat** — halaman lain yang perlu dibaca lebih dulu.
- **Contoh** — kode minimal yang bisa ditempel.
- **Output yang diharapkan** — hasil persis yang seharusnya muncul.
- **Referensi API** — signature, parameter, dan nilai default.
- **Kesalahan umum** — pesan error nyata beserta penyebabnya.
- **Sumber** — file di repo yang menjadi acuan.
- **Status verifikasi** — apakah isi halaman sudah dibuktikan dengan contoh
  yang berjalan, atau masih diturunkan dari membaca kode.

Halaman di `99-referensi/` memakai format berbeda: satu file per kelas, dengan
function dikelompokkan menjadi **Method statis**, **Method instance**, dan
**Helper** (helper diletakkan di bagian bawah). Setiap function punya heading
berisi nama parameter, tabel parameter (tipe, default, keterangan), dan nilai
kembalian. Method tanpa parameter tidak memakai tabel. Bagian **Sumber** dan
**Status verifikasi** tetap ada, sedangkan contoh tidak diulang karena sudah
ada di `01-memulai/`.

Halaman berstatus **BELUM DIVERIFIKASI** berarti perilakunya disimpulkan dari
kode sumber dan belum pernah dijalankan. Jangan menganggapnya sebagai jaminan.

## Sebagai knowledge base AI

Dokumentasi ini juga dipakai sebagai sumber untuk agen AI, jadi setiap halaman
memuat YAML front matter:

```yaml
---
id: database-skema-tabel          # unik, dipakai sebagai kunci dokumen
title: Skema Tabel
type: panduan                     # panduan | referensi | indeks
class: Selvi\Database\Builder\DDL\SchemaBuilder  # hanya di 99-referensi/
tags: [database, ddl, create-table, alter-table]
prerequisites: [database-koneksi] # id halaman yang perlu dibaca dulu
verified: 2026-10-29              # tanggal, partial, atau false
applies_to: Selvi 3.x
---
```

Aturan pengindeksan yang disarankan:

- Potong `01-memulai/` dan `02-database/` per heading `##` (satu bagian satu
  chunk), dan `99-referensi/` per heading `###` (satu method satu chunk).
- Pertahankan hierarki heading sebagai metadata chunk. Heading method referensi
  sudah memuat nama kelas (mis. `AlterBlueprint::dropForeign($name)`), sehingga
  chunk tetap jelas walau dipisah dari halamannya.
- Jangan memakai isi **Status verifikasi** sebagai spesifikasi. Bagian itu
  catatan pengujian pada lingkungan tertentu; statusnya dirangkum di field
  `verified`.
- Signature kanonik ada di `99-referensi/`. Halaman bab hanya menampilkan
  contoh; bila keduanya berbeda, referensi yang benar.
- Indeks ringkas seluruh halaman beserta `id`-nya ada di
  [llms.txt](llms.txt).

## Sumber kebenaran

1. `src/` — perilaku framework yang sebenarnya.
2. `tests/` — satu-satunya contoh yang terbukti berjalan.
3. `docs/` — penjelasan; jika bertentangan dengan `src/`, kode yang benar.

## Menguji panduan

Alur pengujian, perintah, dan hasil yang diharapkan ada di
[tests/README.md](../tests/README.md). Setiap halaman punya aplikasi ujinya
sendiri, misalnya `tests/01.01-instalasi/` untuk halaman ini, sehingga bisa
diaudit per halaman.

`README.md` di root hanya ringkasan cepat, bukan acuan lengkap.
