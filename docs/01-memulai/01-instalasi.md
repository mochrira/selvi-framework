# Instalasi

Memasang Selvi Framework dan membuat satu route `/` dengan function (closure)
sebagai controller.

## Yang dibutuhkan

- PHP 8.1 atau lebih baru.
- Composer.
- Apache dengan `mod_rewrite` aktif dan `AllowOverride All` pada direktori
  proyek.

## 1. Pasang framework

Buat direktori proyek di dalam document root Apache, lalu jalankan:

```sh
mkdir selvi-app
cd selvi-app
composer require mochrira/selvi-framework
```

Perintah ini membuat `vendor/` yang berisi framework dan autoload-nya.

## 2. Buat `index.php`

```php
<?php

require __DIR__.'/vendor/autoload.php';

\Selvi\Routing\Route::get('/', function () {
    return response('Halo, Selvi Framework!');
});

\Selvi\Framework::run();
```

- `Route::get('/', ...)` mendaftarkan route `GET /`.
- Function di dalamnya adalah controller-nya.
- `response()` membungkus teks menjadi respons HTTP.
- `Framework::run()` menjalankan aplikasi.

## 3. Buat `.htaccess`

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [L]
```

Semua request diarahkan ke `index.php`. File ini hanya dibaca Apache jika
`AllowOverride All` diizinkan untuk direktori proyek.

## 4. Buka di browser

```
http://localhost/selvi-app/
```

Hasilnya:

```
Halo, Selvi Framework!
```

Atau lewat terminal:

```sh
curl -i http://localhost/selvi-app/
```

```
HTTP/1.1 200 OK
...
Halo, Selvi Framework!
```

Selesai. Sampai titik ini aplikasi sudah berjalan.

## Catatan

- Gunakan garis miring di akhir URL. Apache mengalihkan `.../selvi-app`
  menjadi `.../selvi-app/` dengan status 301.
- Membuka URL yang tidak terdaftar menghasilkan 500 tanpa isi. Itu normal pada
  tahap ini karena handler exception belum didaftarkan.

## Kesalahan umum

| Gejala | Penyebab | Perbaikan |
|---|---|---|
| Apache menampilkan 404 untuk semua URL | `mod_rewrite` mati atau `AllowOverride None` | aktifkan module, set `AllowOverride All`, restart Apache |
| `Call to undefined function response()` | `vendor/autoload.php` belum di-require | tambahkan `require` di baris atas |
| Kode PHP terlihat mentah di browser | handler PHP di Apache tidak aktif | aktifkan `mod_php` atau PHP-FPM |
| Route jalan di root, tapi 404 di subfolder | `DocumentRoot` menunjuk folder induk | arahkan ke direktori proyek |
| URL tanpa `/` di akhir | Apache melakukan redirect `mod_dir` | pakai `/` di akhir |
| URL tak terdaftar → 500 tanpa isi | handler exception belum didaftarkan | normal pada tahap ini |

## Referensi API

| Simbol | Signature |
|---|---|
| `response()` | `response(?string $content = '', int $code = 200): Response` |
| `Route::get()` | `Route::get(string $uri, callable\|string\|array $callback): Route` |
| `Framework::run()` | `Framework::run(): void` |

## Sumber

- `composer.json` — versi PHP dan autoload.
- `src/Helpers/ResponseHelper.php` — helper `response()`.
- `src/Framework.php` — entry point.
- `tests/.htaccess` — aturan rewrite yang sama, tanpa bagian tambahan.

## Status verifikasi

Diuji pada 28 Oktober 2026 dengan container `mochrira/dev` (PHP 8.4.14,
Apache + `.htaccess`) dan aplikasi `tests/01.01-instalasi/` yang isinya persis
seperti panduan di atas. Request diuji dari host lewat
`http://localhost:8089/01.01-instalasi/`:

| Request | Hasil |
|---|---|
| `GET /01.01-instalasi/` | `200` — `Halo, Selvi Framework!` |
| `GET /01.01-instalasi/?x=1` | `200` — `Halo, Selvi Framework!` |
| `GET /01.01-instalasi` | `301` — redirect ke versi bergaris miring |
| `GET /01.01-instalasi/tidak-ada` | `500` tanpa isi |
| `php -l index.php` | tanpa error |

Autoload diuji dengan `composer require mochrira/selvi-framework` sungguhan
dari Packagist (versi 3.6.10), bukan symlink. Container `selvi_test_dev` sudah
berjalan sebelum pengujian, sehingga `docker compose` tidak dijalankan ulang.
Langkah lengkap dan artefaknya ada di
[tests/README.md](../../tests/README.md) dan `tests/01.01-instalasi/`.
