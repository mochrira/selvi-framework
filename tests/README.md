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
  hanya untuk perintah CLI (`php -l`), karena PHP tidak tersedia di host.
- **Jangan menjalankan `composer install`/`composer require` di folder uji**
  selain `01.01-instalasi/`. Folder uji lain memuat `vendor/` workspace
  dengan `require __DIR__.'/../../vendor/autoload.php';`, seperti
  `tests/index.php`. Dengan begitu yang diuji adalah `src/` repo, bukan paket
  terbit.

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

## Langkah 4 — uji halaman response (`01.02-response/`)

Folder ini menguji `docs/01-memulai/02-response.md`. Tidak ada
`composer install` di sini: `index.php` memuat
`__DIR__.'/../../vendor/autoload.php'`, yaitu `vendor/` workspace, sama seperti
`tests/index.php`. Jadi yang diuji adalah `src/` repo.

```sh
docker exec selvi_test_dev sh -lc '
php -l /workspace/tests/01.02-response/index.php
php -l /workspace/tests/01.02-response/views/home.php'

for u in / /json /json-header /halaman /halaman-hilang /tidak-ditemukan /tanpa-isi /tanpa-return; do
  printf '%-22s ' "/01.02-response$u"
  curl -s -o /dev/null -w '%{http_code}\n' "http://localhost:8089/01.02-response$u"
done

curl -si http://localhost:8089/01.02-response/json-header | grep -i '^content-type'
```

Hasil terverifikasi:

| Endpoint | Status | Catatan |
|---|---|---|
| `/01.02-response/` | `200` | teks biasa |
| `/01.02-response/json` | `200` | JSON rapi, `Content-Type: text/html` |
| `/01.02-response/json-header` | `200` | `Content-Type: application/json` |
| `/01.02-response/halaman` | `200` | HTML utuh dari `views/home.php` |
| `/01.02-response/halaman-hilang` | `500` | sengaja salah: template tidak ada |
| `/01.02-response/tidak-ditemukan` | `404` | body `Data tidak ditemukan` |
| `/01.02-response/tanpa-isi` | `204` | tanpa body |
| `/01.02-response/tanpa-return` | `500` | sengaja salah: `echo` tanpa `return` |

## Langkah 5 — uji halaman request (`01.03-request/`)

Folder ini menguji `docs/01-memulai/03-request.md`. Sama seperti folder uji
lain, `index.php` memuat `vendor/` workspace. `.htaccess` di folder ini
memuat aturan passthrough `Authorization`, karena halaman request menjelaskan
aturan tersebut.

```sh
docker exec selvi_test_dev php -l /workspace/tests/01.03-request/index.php

B=http://localhost:8089/01.03-request
curl -s "$B/halo?nama=Budi"; echo
curl -s "$B/halo"; echo
curl -s -X POST -H 'Content-Type: application/json' -d '{"nama":"Budi"}' "$B/kontak"; echo
curl -s -X POST -d 'nama=Budi' "$B/form"; echo
curl -s -H 'X-Token: abc123' "$B/token"; echo
curl -s "$B/metode"; echo
```

Hasil terverifikasi:

| Request | Status | Hasil |
|---|---|---|
| `GET /01.03-request/halo?nama=Budi` | `200` | `Halo, Budi!` |
| `GET /01.03-request/halo` | `200` | `Halo, Pengunjung!` |
| `POST /01.03-request/kontak` (JSON) | `201` | JSON `diterima` |
| `POST /01.03-request/form` | `200` | `Halo, Budi` |
| `GET /01.03-request/token` + `X-Token` | `200` | `abc123` |
| `GET /01.03-request/metode` | `200` | `GET` |

## Langkah 6 — cek sintaks source

```sh
docker exec selvi_test_dev sh -lc '
for f in $(find /workspace/src -name "*.php"); do
  php -l "$f" > /dev/null || echo "FAIL $f"
done'
```

Tidak ada output berarti semua file lolos.

## Catatan penting

- Hanya `01.01-instalasi/` yang menguji **paket terbit di Packagist**, karena
  halaman itu memang menjelaskan alur instalasi user.
- Folder uji halaman lain (mis. `01.02-response/`) memuat `vendor/` workspace
  lewat `require __DIR__.'/../../vendor/autoload.php';`, sehingga yang diuji
  adalah `src/` lokal. Jangan menjalankan Composer di folder-folder itu.
- Harness `tests/index.php` juga memuat `vendor/` repo (autoload root
  memetakan `Selvi\` ke `src/`).
- Pola "route dipisah ke `app/Config/routes.php`" sudah dibuktikan oleh harness
  itu sendiri: `tests/index.php` me-`require` `app/Config/routes.php`, lalu
  `Framework::run()`.
- `tests/private/.ENV` berisi kredensial database lokal dan diabaikan git.
- Port `8090` tidak dipakai oleh compose saat ini; mapping-nya `8089:80`.
  Ubah `ports:` bila ingin memakai port lain.
