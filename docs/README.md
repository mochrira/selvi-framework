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
5. [Struktur folder aplikasi](01-memulai/05-struktur-folder.md) — selesai.

Halaman yang belum ditulis tidak ditautkan, agar tidak ada link mati.

## Data Object (DTO)

Bab `02-dto/` menjelaskan pemetaan objek ke properti bertipe:

1. [Data Object (DTO)](02-dto/01-data-object.md) — selesai.
2. [Properti bersarang](02-dto/02-properti-nested.md) — selesai.
3. [Cast dan serialize](02-dto/03-cast-dan-serialize.md) — selesai.

## Model

Bab `03-model/` membahas model aktif-record di atas `DataObject`:

1. [Model](03-model/01-model.md) — selesai.
2. [Relasi](03-model/02-relasi.md) — selesai.
3. [Konversi value](03-model/03-konversi-value.md) — selesai.

## Database

Bab `04-database/` ditulis bertahap, dimulai dari menyiapkan koneksi:

1. [Koneksi](04-database/01-koneksi.md) — selesai.
2. [Skema tabel](04-database/02-skema-tabel.md) — selesai.
3. [Membaca data](04-database/03-membaca-data.md) — selesai.
4. [Menulis data](04-database/04-menulis-data.md) — selesai.
5. [Migrasi](04-database/05-migrasi.md) — selesai.
6. [Seeder](04-database/06-seeder.md) — selesai.

## API

Bab `05-api/` membahas pembuatan endpoint dari migrasi sampai pengujian:

1. [Panduan Endpoint CRUD API](05-api/01-crud-endpoint.md) — migrasi tabel,
   spesifikasi OpenAPI di `specs/`, Swagger UI, persetujuan spec, model, route,
   controller, dan pengujian.

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

Kelas yang dipakai di bab `02-dto/`:

8. [`Selvi\Data\DataObject`](99-referensi/14-data-object.md) — basis DTO.
9. [`Selvi\Data\DataProperty`](99-referensi/15-data-property.md) — attribute properti DTO.

Kelas yang dipakai di bab `03-model/`:

10. [`Selvi\Model`](99-referensi/21-model.md) — basis model.
11. [`Selvi\Database\Builder\DML\ModelQuery`](99-referensi/22-model-query.md) — query model.
12. [`Selvi\Collection`](99-referensi/23-collection.md) — daftar hasil query.
13. [`Selvi\Model\Attributes\Table`](99-referensi/24-table.md) — tabel dan koneksi model.
14. [`Selvi\Model\Attributes\Column`](99-referensi/25-column.md) — pemetaan kolom dan expression SELECT.
15. [`Selvi\Model\Attributes\NotMapped`](99-referensi/26-not-mapped.md) — properti non-kolom.
16. [`Selvi\Model\Attributes\BelongsTo`](99-referensi/27-belongs-to.md) — relasi milik.
17. [`Selvi\Model\Attributes\Aggregate`](99-referensi/28-aggregate.md) — relasi agregat dan expression.
18. [`Selvi\Database\Casting\Converter`](99-referensi/29-converter.md) — cast dan serialisasi.

Kelas yang dipakai di bab `04-database/`:

19. [`Selvi\Database\Manager`](99-referensi/08-manager.md) — registry koneksi.
20. [`Selvi\Database\DB`](99-referensi/09-db.md) — facade pembuat builder.
21. [`Selvi\Database\Builder\DDL\SchemaBuilder`](99-referensi/10-schema-builder.md) — operasi DDL.
22. [`Selvi\Database\Builder\DDL\Blueprint`](99-referensi/11-blueprint.md) — definisi `CREATE TABLE`.
23. [`Selvi\Database\Builder\DDL\AlterBlueprint`](99-referensi/12-alter-blueprint.md) — operasi `ALTER TABLE`.
24. [`Selvi\Database\Builder\DDL\Clauses\ColumnClause`](99-referensi/13-column-clause.md) — modifier kolom.
25. [`Selvi\Database\Builder\DML\QueryBuilder`](99-referensi/16-query-builder.md) — SELECT/INSERT/UPDATE/DELETE.
26. [`Selvi\Database\Interfaces\IResult`](99-referensi/17-hasil-query.md) — hasil query.
27. [`Selvi\Database\Schema`](99-referensi/18-schema.md) — handle DDL terikat koneksi.
28. [`Selvi\Database\Migration`](99-referensi/19-migration.md) — perintah migrasi.
29. [`Selvi\Database\Seeder`](99-referensi/20-seeder.md) — perintah seeder.
30. [`Selvi\Database\Builder\DML\Clauses\SelectNode`](99-referensi/30-select-node.md) — node AST untuk `SELECT`, literal, fungsi, dan raw.

## Status penulisan

| Folder | Status |
|---|---|
| `01-memulai/` | selesai (5 halaman) |
| `02-dto/` | selesai (3 halaman) |
| `03-model/` | selesai (3 halaman) |
| `04-database/` | selesai (6 halaman) |
| `05-api/` | selesai (1 halaman) |
| `99-referensi/` | selesai (30 halaman) |
| bagian lain | belum ditulis |

## Cara membaca halaman

Ada dua template halaman, mengikuti `type` di front matter.

**Panduan** (`type: panduan`, folder `01-memulai/`, `02-dto/`, `03-model/`,
`04-database/`, dan `05-api/`):

1. Paragraf pembuka — masalah yang diselesaikan halaman dan kapan dipakai.
2. **Jawaban singkat** — satu blok kode minimal (≤ 20 baris) beserta hasil
   singkat. Ini chunk pertama yang terambil saat pencarian, jadi harus cukup
   menjawab pertanyaan utama halaman tanpa membaca seluruh langkah.
3. **Prasyarat** — halaman lain yang perlu dibaca lebih dulu, plus
   konfigurasi/namespace yang harus sudah ada.
4. Langkah bernomor `## 1. ...`, `## 2. ...` — tiap langkah memuat contoh kode
   beserta output yang diharapkan. **Contoh** dan **Output yang diharapkan**
   tidak wajib menjadi heading sendiri karena melekat pada langkah.
5. **Referensi API** — signature, parameter, dan nilai default.
6. **Kesalahan umum** — pesan error nyata beserta penyebabnya.
7. **Sumber** — file di repo yang menjadi acuan.
8. **Status verifikasi** — bukti pengujian: endpoint, status, hasil, dan hal
   yang belum diuji.

Halaman yang memuat alur panjang, misalnya
[Panduan Endpoint CRUD API](05-api/01-crud-endpoint.md), boleh menambahkan
**Tujuan** eksplisit sebelum **Prasyarat**.

**Referensi** (`type: referensi`, folder `99-referensi/`) memakai format
berbeda: satu file per kelas, dengan
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

- Potong `01-memulai/`, `02-dto/`, `03-model/`, `04-database/`, dan `05-api/`
  per heading `##` (satu bagian satu chunk), dan `99-referensi/` per heading
  `###` (satu method satu chunk). Chunk pertama panduan adalah **Jawaban
  singkat**, sehingga pertanyaan umum bisa dijawab tanpa memuat seluruh
  halaman.
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
