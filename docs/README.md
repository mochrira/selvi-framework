# Dokumentasi Selvi Framework

Knowledge base untuk developer dan agent AI yang bekerja dengan Selvi Framework.
Dokumentasi ini ditulis sebagai Markdown biasa, tanpa generator situs.

## Mulai di sini

Urutan baca untuk orang maupun agent yang baru mengenal framework ini:

1. [Instalasi](01-memulai/01-instalasi.md) — memasang framework sampai route `/` bisa diakses.
2. [Struktur proyek](01-memulai/02-struktur-proyek.md) — file mana yang wajib, mana yang opsional.
3. [Siklus request](01-memulai/03-siklus-request.md) — kerangka yang menghubungkan semua fitur.

## Status penulisan

| Folder | Status |
|---|---|
| `01-memulai/` | selesai |
| `02-http/` | belum ditulis |
| `03-data/` | belum ditulis |
| `04-cli/` | belum ditulis |
| `05-resep/` | belum ditulis |
| `06-referensi/` | belum ditulis |

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
