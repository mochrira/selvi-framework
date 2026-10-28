# Cara Mengetes Framework dan Panduan

Folder ini punya dua peran:

1. **Harness** — aplikasi contoh di `app/` yang memuat `src/` repo, dipakai
   untuk menguji perubahan kode framework.
2. **Verifikasi panduan** — setiap halaman `docs/` punya aplikasi uji sendiri,
   dengan nama folder sama seperti nama file dokumennya.

Konvensi penamaan: halaman `docs/01-memulai/01-instalasi.md` diuji oleh
`tests/01.01-instalasi/`. Dengan begitu audit bisa dilakukan per halaman.

Tidak ada PHPUnit di repo ini. Semua verifikasi dilakukan lewat HTTP.

## Lingkungan uji

| Hal | Nilai |
|---|---|
| Image | `mochrira/dev` (dari `.docker/compose.yaml`) |
| Container | `selvi_test_dev` |
| Mount | root repo → `/workspace` |
| DocumentRoot Apache | `/workspace/tests` |
| Port host | `8089` → container `80` |

Konsekuensinya:

- Seluruh isi repo langsung terlihat di container; tidak perlu menyalin file.
- Folder baru di dalam `tests/` **langsung** bisa diakses di
  `http://localhost:8089/{nama-folder}/`.
- Port mengikuti `ports:` di `.docker/compose.yaml`. Nilai saat ini
  `8089:80`. Bila mapping diubah (misalnya ke `8090:80`), sesuaikan URL-nya.

## Aturan dasar

- Hanya memakai image dari `.docker/compose.yaml`. Jangan memakai image lain.
- **Selalu cek container lebih dulu.** Container sering sudah berjalan sebelum
  pengujian dimulai.
- Jangan menghapus folder verifikasi panduan; isinya bukti uji yang perlu bisa
  diperiksa ulang.
- Jangan menjalankan `docker compose down` pada container yang tidak Anda
  nyalakan sendiri.
- Uji HTTP dilakukan dari **host** ke `http://localhost:8089`. `docker exec`
  hanya untuk perintah CLI (`composer`, `php -l`), karena PHP tidak tersedia
  di host.

## Langkah 0 — cek container (wajib)

```sh
docker ps --filter name=selvi_test_dev --format '{{.Names}} | {{.Status}}'
```

| Hasil | Tindakan |
|---|---|
| `selvi_test_dev \| Up ...` | lanjut ke Langkah 2 |
| kosong atau `Exited` | jalankan Langkah 1 |

## Langkah 1 — jalankan container

```sh
cd tests
docker compose -f .docker/compose.yaml up -d
```

Compose membutuhkan network eksternal `environment` dan `database`. Tunggu
sampai status `Up`:

```sh
docker ps --filter name=selvi_test_dev --format '{{.Status}}'
```

## Langkah 2 — baseline harness

```sh
curl -s -w '\n%{http_code}\n' http://localhost:8089/
```

Hasil yang diharapkan: `404` dengan body JSON.

```json
{
    "code": "http\/error",
    "message": "Route GET '\/'  not found",
    "request": {
        "uri": "\/",
        "method": "GET"
    }
}
```

404 ini benar: `app/Config/routes.php` tidak mendaftarkan route `/`. Kalau
hasilnya berbeda, Apache atau handler exception belum siap, dan pengujian
berikutnya tidak valid.

## Langkah 3 — uji halaman instalasi (`01.01-instalasi/`)

Folder ini menguji `docs/01-memulai/01-instalasi.md`. Strukturnya mengikuti
panduan: `index.php`, `.htaccess`, dan `vendor/` hasil Composer.

```sh
docker exec -w /workspace/tests/01.01-instalasi selvi_test_dev \
  composer require mochrira/selvi-framework --no-interaction
```

Perintah itu sudah dijalankan; `vendor/` diabaikan git. Untuk mengulang dari
nol, hapus `vendor/`, `composer.json`, dan `composer.lock` di folder tersebut,
lalu jalankan perintah di atas.

Cek sintaks dan endpoint:

```sh
docker exec selvi_test_dev php -l /workspace/tests/01.01-instalasi/index.php

for u in /01.01-instalasi/ '/01.01-instalasi/?x=1' /01.01-instalasi /01.01-instalasi/tidak-ada; do
  printf '%-30s ' "$u"
  curl -s -o /dev/null -w '%{http_code}\n' "http://localhost:8089$u"
done
```

Hasil terverifikasi (28 Oktober 2026, `mochrira/selvi-framework` 3.6.10,
PHP 8.4.14):

| Request | Status | Body |
|---|---|---|
| `GET /01.01-instalasi/` | `200` | `Halo, Selvi Framework!` |
| `GET /01.01-instalasi/?x=1` | `200` | `Halo, Selvi Framework!` |
| `GET /01.01-instalasi` | `301` | redirect Apache ke versi bergaris miring |
| `GET /01.01-instalasi/tidak-ada` | `500` | kosong (handler exception belum didaftarkan) |
| `php -l index.php` | — | tanpa error |

## Langkah 4 — cek sintaks source

```sh
docker exec selvi_test_dev sh -lc '
for f in $(find /workspace/src -name "*.php"); do
  php -l "$f" > /dev/null || echo "FAIL $f"
done'
```

Tidak ada output berarti semua file lolos.

## Catatan penting

- Folder verifikasi panduan menguji **paket terbit di Packagist**, karena
  itulah yang diterima user saat mengikuti panduan.
- Perubahan di `src/` diuji lewat harness `tests/index.php`, yang memuat
  `vendor/` repo (autoload root memetakan `Selvi\` ke `src/`).
- Pola "route dipisah ke `app/Config/routes.php`" (dibahas di
  `docs/01-memulai/02-struktur-proyek.md`) sudah dibuktikan oleh harness itu
  sendiri: `tests/index.php` me-`require` `app/Config/routes.php`, lalu
  `Framework::run()`.
- `tests/private/.ENV` berisi kredensial database lokal dan diabaikan git.
- Port `8090` tidak dipakai oleh compose saat ini; mapping-nya `8089:80`.
  Ubah `ports:` bila ingin memakai port lain.
