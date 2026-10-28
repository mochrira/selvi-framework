# Dokumentasi Selvi Framework

Knowledge base untuk developer dan agent AI yang bekerja dengan Selvi Framework.
Dokumentasi ini ditulis sebagai Markdown biasa, tanpa generator situs.

## Mulai di sini

Bagian `01-memulai/` ditulis bertahap dengan urutan:

1. [Instalasi](01-memulai/01-instalasi.md) — selesai.
2. [Response](01-memulai/02-response.md) — selesai.
3. [Request](01-memulai/03-request.md) — selesai.
4. `04-controller.md` — belum ditulis.

Halaman yang belum ditulis tidak ditautkan, agar tidak ada link mati.

## Referensi API

Rujukan per kelas, diringkas dari halaman-halaman `01-memulai/`:

1. [`Framework::run()`](02-referensi/01-framework.md) — titik masuk aplikasi.
2. [`Selvi\Input\Request`](02-referensi/02-request.md) — pembaca data request.
3. [`Selvi\Output\Response`](02-referensi/03-response.md) — respons teks dan cookie.
4. [`Selvi\Output\JsonResponse`](02-referensi/04-json-response.md) — respons JSON.
5. [`Selvi\View`](02-referensi/05-view.md) — perender template HTML.

## Status penulisan

| Folder | Status |
|---|---|
| `01-memulai/` | 3 dari 4 halaman |
| `02-referensi/` | selesai (5 halaman) |
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

Halaman di `02-referensi/` memakai format berbeda: satu file per kelas, dengan
function dikelompokkan menjadi **Method statis**, **Method instance**, dan
**Helper** (helper diletakkan di bagian bawah). Setiap function punya heading
berisi nama parameter, tabel parameter (tipe, default, keterangan), dan nilai
kembalian. Method tanpa parameter tidak memakai tabel. Bagian **Sumber** dan
**Status verifikasi** tetap ada, sedangkan contoh tidak diulang karena sudah
ada di `01-memulai/`.

Halaman berstatus **BELUM DIVERIFIKASI** berarti perilakunya disimpulkan dari
kode sumber dan belum pernah dijalankan. Jangan menganggapnya sebagai jaminan.

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
