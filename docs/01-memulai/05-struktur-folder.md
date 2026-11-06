---
id: struktur-folder
title: Struktur Folder Aplikasi
type: panduan
tags: [struktur-folder, bootstrap, public, app, autoload, psr-4, htaccess]
prerequisites: [instalasi, controller]
verified: 2026-11-05
applies_to: Selvi 3.x
---

# Struktur Folder Aplikasi

[Instalasi](01-instalasi.md) menaruh semua kode di satu `index.php` agar
request pertama cepat jalan. Saat controller, config, dan model bertambah,
kelompokkan kode aplikasi ke `app/` dan sisakan `index.php`, `bootstrap.php`,
serta `.htaccess` di root proyek.

Layout di halaman ini mengikuti server yang document root-nya sudah ditetapkan
(mis. folder proyek langsung berada di `htdocs/` atau `public_html/`), sehingga
tidak perlu mengubah konfigurasi server. Halaman ini memakai struktur yang sama
dengan panduan controller, panduan CRUD API, dan aplikasi uji
`tests/01.05-struktur-folder/`:

- Folder proyek **adalah** document root. Tidak ada subfolder `public/`,
  sehingga `DocumentRoot` di server tidak perlu diubah.
- `app/` — kode aplikasi.
- `bootstrap.php` — autoload dan handler exception, dimuat sebelum route.
- `index.php` — front controller yang memuat bootstrap dan daftar route.
- `.htaccess` — mengarahkan URI ke `index.php` sekaligus menolak akses langsung
  ke `app/`, `vendor/`, `private/`, `bootstrap.php`, `composer.json`, dan file
  berawalan titik.

## Jawaban singkat

```text
selvi-app/              ← document root
├── app/Config/routes.php
├── app/Controllers/HaloController.php
├── .htaccess           ← rewrite + blokir app/, vendor/, private/
├── bootstrap.php
├── index.php
└── vendor/
```

`index.php` memuat `bootstrap.php` dan `app/Config/routes.php`, lalu memanggil
`\Selvi\Framework::run()`.

## Prasyarat

- [Instalasi](01-instalasi.md) sudah berhasil dan `vendor/` tersedia.
- [Controller](04-controller.md) sudah dibaca, termasuk pemetaan PSR-4
  `App\` → `app/`.

## 1. Struktur yang direkomendasikan

```text
selvi-app/                  ← folder ini = document root
├── app/
│   ├── Config/
│   │   ├── database.php
│   │   ├── exception.php
│   │   └── routes.php
│   ├── Controllers/
│   │   └── HaloController.php
│   ├── DTO/
│   ├── Models/
│   └── Services/
│       └── SapaanService.php
├── private/                ← file rahasia, diblokir .htaccess
│   └── contoh-rahasia.txt
├── .htaccess
├── bootstrap.php
├── composer.json
├── index.php
└── vendor/
```

Folder `DTO/`, `Models/`, `database.php`, dan `private/` opsional; tambahkan
saat dipakai.
Yang wajib untuk alur di halaman ini hanya `app/Config/routes.php`,
`app/Controllers/`, `index.php`, dan `bootstrap.php`.

Karena semua file berada di document root, kode aplikasi bisa diakses lewat URL
bila tidak diblokir. Bagian 6 memasang aturan `.htaccess` yang menolak akses
tersebut.

## 2. `bootstrap.php`

```php
<?php

declare(strict_types=1);

use Selvi\Exception\Handler;

require __DIR__.'/vendor/autoload.php';

Handler::setDefaultHandlers();
Handler::listen();
```

- Autoload dimuat sekali di sini, bukan di `index.php`.
- `Handler::setDefaultHandlers()` dan `Handler::listen()` membuat error tampil
  sebagai JSON. Tanpa keduanya, error bisa keluar sebagai halaman HTML.
- Bila memakai database, muat `app/Config/database.php` setelah baris handler;
  contohnya ada di [Koneksi database](../04-database/01-koneksi.md).

## 3. `index.php`

```php
<?php

require __DIR__.'/bootstrap.php';
require __DIR__.'/app/Config/routes.php';

\Selvi\Framework::run();
```

File ini hanya memuat bootstrap dan daftar route, lalu menyerahkan sisa proses
ke `Framework::run()`.

## 4. `app/Config/routes.php`

```php
<?php

use Selvi\Routing\Route;

Route::get('/', 'App\Controllers\HaloController@index');
Route::get('/sapa/{nama}', 'App\Controllers\HaloController@sapa');
```

Daftar route adalah file biasa yang di-`require`; tidak ada cache atau
registrasi otomatis.

## 5. Namespace dan autoload

Petakan namespace aplikasi ke folder `app/` pada `composer.json`, lalu jalankan
`composer dump-autoload`:

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "app/"
        }
    }
}
```

Dengan pemetaan itu, `App\Controllers\HaloController` dicari di
`app/Controllers/HaloController.php` dan `App\Services\SapaanService` di
`app/Services/SapaanService.php`. Detail pemakaian controller ada di
[Controller](04-controller.md).

## 6. `.htaccess` dan cara membuka aplikasi

`.htaccess` di folder proyek melakukan dua hal: menolak akses langsung ke file
internal, lalu mengarahkan URI lain ke `index.php`.

```apache
RewriteEngine On

# Tolak akses langsung ke kode, dependency, dan file sensitif.
RewriteRule ^(app|vendor|private)(/|$) - [F,L]
RewriteRule ^(bootstrap\.php|composer\.(json|lock)|console\.php)$ - [F,L]
RewriteRule ^\.(?!well-known(/|$)) - [F,L]

# URI lain diarahkan ke front controller.
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [L]
```

- Folder proyek langsung menjadi document root, jadi aplikasi dibuka lewat
  `http://localhost/selvi-app/` seperti pada [Instalasi](01-instalasi.md).
- Aturan blokir menghasilkan `403 Forbidden` untuk
  `http://localhost/selvi-app/app/Config/routes.php`,
  `http://localhost/selvi-app/vendor/autoload.php`,
  `http://localhost/selvi-app/private/contoh-rahasia.txt`, dan
  `http://localhost/selvi-app/bootstrap.php`.
- Simpan file rahasia (kredensial tambahan, kunci, arsip) di `private/`, bukan
  di `app/Config/` atau root proyek. Folder itu tetap dapat dibaca kode PHP
  lewat filesystem, tetapi tidak dapat diakses lewat HTTP.
- Folder `.well-known/` dikecualikan dari blokir file berawalan titik agar
  validasi sertifikat (ACME/Let's Encrypt) tetap berjalan.
- Aturan ini bekerja di Apache dengan `AllowOverride All`. Nginx tidak membaca
  `.htaccess`; bila server memakai Nginx, blokir path tersebut harus ditulis di
  konfigurasi server.

### Catatan keamanan

Layout ini menaruh kode di document root. Tanpa aturan blokir di atas,
`app/Config/database.php`, `vendor/`, `private/`, `composer.json`, dan `.ENV`
dapat dibaca siapa pun yang menebak URL-nya. Aturan blokir adalah lapisan
pertama, bukan pengganti pemisahan file: jangan menaruh file rahasia di folder
yang tidak tercakup aturan tersebut. Bila `DocumentRoot` bisa diubah, layout yang
lebih aman adalah memisahkan `app/`, `bootstrap.php`, dan `vendor/` ke luar
document root, lalu menjadikan subfolder `public/` sebagai `DocumentRoot`.
Yang dipakai di halaman ini adalah layout untuk server yang document root-nya
sudah ditetapkan.

## Referensi API

| Simbol | Signature | Keterangan |
|---|---|---|
| `Framework::run()` | `run(): void` | Menjalankan siklus request; proses berhenti di `send()`. |
| `Route::get()` | `Route::get(string $uri, callable\|string\|array $callback): Route` | Juga tersedia `post()`, `patch()`, `delete()`, `options()`. |
| `Handler::setDefaultHandlers()` | `setDefaultHandlers()` | Mendaftarkan handler JSON bawaan. |
| `Handler::listen()` | `listen()` | Memasang handler sebagai exception handler PHP. |

Rincian tiap simbol ada di [`Framework::run()`](../99-referensi/01-framework.md)
dan [`Selvi\Routing\Route`](../99-referensi/02-route.md).

## Kesalahan umum

| Gejala | Penyebab | Perbaikan |
|---|---|---|
| `Class "App\Controllers\HaloController" does not exist` | Namespace tidak cocok dengan path, atau `App\` belum dipetakan di autoload | samakan namespace dengan `app/`, lalu `composer dump-autoload` |
| Semua URI kecuali `/` menghasilkan 404 | `mod_rewrite`/`.htaccess` tidak aktif di folder document root | aktifkan `AllowOverride All`, letakkan `.htaccess` di folder proyek |
| `app/Config/routes.php` atau `private/` bisa dibuka lewat browser | aturan blokir pada `.htaccess` belum dipasang | tambahkan tiga `RewriteRule` blokir dari bagian 6 |
| Error tampil sebagai halaman HTML, bukan JSON | `Handler::setDefaultHandlers()` dan `Handler::listen()` belum dipanggil | panggil keduanya di `bootstrap.php` |
| `Failed opening required '.../vendor/autoload.php'` | `bootstrap.php` dipindah tanpa menyesuaikan `__DIR__` | sesuaikan path relatif ke `vendor/` |

## Sumber

- `src/Framework.php` — urutan `run()` dan pengiriman response.
- `src/Helpers/LoadHelper.php` — helper `inject()` dan `view()`.
- `src/Exception/Handler.php` — `setDefaultHandlers()` dan `listen()`.
- `tests/01.05-struktur-folder/index.php`,
  `tests/01.05-struktur-folder/bootstrap.php`,
  `tests/01.05-struktur-folder/.htaccess`,
  `tests/01.05-struktur-folder/app/Config/routes.php`,
  `tests/01.05-struktur-folder/app/Controllers/HaloController.php`, dan
  `tests/01.05-struktur-folder/app/Services/SapaanService.php` — aplikasi uji
  halaman ini.

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 5 November 2026 dengan PHP 8.4.14 lewat
`http://localhost:8089/01.05-struktur-folder/`. Aplikasi uji memuat `vendor/`
workspace, jadi yang diuji adalah `src/` repo ini.

Folder uji hanya memuat autoload repo, sedangkan repo ini tidak memetakan
namespace `App\`. Karena itu `bootstrap.php` uji mendaftarkan PSR-4 `App\` →
`app/` lewat `spl_autoload_register()`. Aplikasi nyata memakai
`autoload.psr-4` seperti pada bagian 5.

| Endpoint | Status | Hasil |
|---|---|---|
| `GET /01.05-struktur-folder/` | `200` | `Halo dari index.php` |
| `GET /01.05-struktur-folder/sapa/Budi` | `200` | `Halo, Budi!` |
| `GET /01.05-struktur-folder/tidak-ada` | `404` | JSON error `Route GET ... not found` |
| `GET /01.05-struktur-folder/app/Config/routes.php` | `403` | diblokir `.htaccess` |
| `GET /01.05-struktur-folder/bootstrap.php` | `403` | diblokir `.htaccess` |
| `GET /01.05-struktur-folder/private/contoh-rahasia.txt` | `403` | diblokir `.htaccess` (folder uji tidak memuat `private/`; tanpa aturan hasilnya `404`) |
| `GET /01.05-struktur-folder/vendor/autoload.php` | `403` | diblokir `.htaccess` (folder `vendor/` uji tidak ada; tanpa aturan hasilnya `404`) |
| `GET /01.05-struktur-folder/composer.json` | `403` | diblokir `.htaccess` (file tidak ada; tanpa aturan hasilnya `404`) |
| `GET /01.05-struktur-folder/.env` | `403` | diblokir `.htaccess` (file tidak ada; tanpa aturan hasilnya `404`) |
| `GET /01.05-struktur-folder/.well-known/test` | `404` | sengaja tidak diblokir, jadi 404 biasa |

Belum diuji: penerapan `AllowOverride All` dan pola blokir yang sama pada
Apache/Nginx produksi.
