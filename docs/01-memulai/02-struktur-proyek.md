# Struktur Proyek

Menjelaskan file yang wajib ada dan di mana kode aplikasi sebaiknya diletakkan.

## Prasyarat

- [Instalasi](01-instalasi.md) sudah berhasil.

## Yang wajib

```
selvi-app/
├── index.php      # mendaftarkan route, lalu Framework::run()
├── .htaccess      # mengarahkan semua request ke index.php
└── vendor/        # hasil composer require
```

Itu saja. Selvi tidak punya perintah generator seperti `make:controller`, dan
tidak memindai folder secara otomatis. Route dan kelas harus didaftarkan manual.

## Struktur yang disarankan

```
selvi-app/
├── index.php
├── .htaccess
├── composer.json
├── vendor/
└── app/
    ├── Config/
    │   └── routes.php      # daftar route
    ├── Controllers/
    └── Models/
```

Nama folder bebas. Yang mengikat hanya mapping PSR-4 di `composer.json`.

## Memisahkan route ke file

`index.php`:

```php
<?php

require __DIR__.'/vendor/autoload.php';
require __DIR__.'/app/Config/routes.php';

\Selvi\Framework::run();
```

`app/Config/routes.php`:

```php
<?php

use Selvi\Routing\Route;

Route::get('/', function () {
    return response('Halo dari routes.php');
});
```

Hasilnya sama dengan versi satu file. Route harus didaftarkan sebelum
`Framework::run()`.

## Autoload kelas aplikasi

Namespace `Selvi\` sudah dipetakan framework. Namespace aplikasi belum, jadi
tambahkan sendiri di `composer.json`:

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "app/"
        }
    }
}
```

Lalu:

```sh
composer dump-autoload
```

Di repo ini, harness memakai mapping `Selvi\Tests\` → `tests/app`, sehingga
controller di `tests/app/Controllers/` bisa dimuat.

## Controller tidak wajib mewarisi kelas

`Selvi\Controller` ada tetapi kosong. Semua controller di
`tests/app/Controllers/` adalah kelas biasa tanpa `extends`. Mewarisi
`Controller` hanya konvensi.

## Contoh di `tests/`

| Path | Isi |
|---|---|
| `tests/index.php` | entry point web |
| `tests/app/Config/routes.php` | daftar route |
| `tests/app/Controllers/` | controller |
| `tests/app/Models/` | model |
| `tests/app/Migrations/` | file migrasi |
| `tests/specs/` | spesifikasi OpenAPI manual |

## Kesalahan umum

| Gejala | Penyebab | Perbaikan |
|---|---|---|
| `Class "App\Controllers\..." not found` | namespace belum dipetakan | tambah PSR-4, jalankan `composer dump-autoload` |
| `require` gagal saat dijalankan dari direktori lain | path relatif terhadap direktori kerja | gunakan `__DIR__` |
| Route baru tidak dikenali | didaftarkan setelah `Framework::run()` | pindahkan ke atas |
| Kelas di `app/` tidak terbaca otomatis | tidak ada auto-discovery | daftarkan route secara eksplisit |

## Referensi API

Tidak ada API baru di halaman ini. Lihat
[referensi API instalasi](01-instalasi.md#referensi-api).

## Sumber

- `composer.json` — mapping PSR-4.
- `tests/index.php`, `tests/app/Config/routes.php` — contoh pemisahan route.
- `src/Controller.php`, `src/Base.php` — kelas dasar kosong.

## Status verifikasi

Varian route terpisah di `app/Config/routes.php` terbukti dari harness
`tests/index.php`, yang me-`require` `tests/app/Config/routes.php` lalu
memanggil `Framework::run()`. Pola inilah yang dijelaskan di halaman ini.

Pemuatan kelas lewat PSR-4 dibuktikan oleh harness `tests/` sendiri, yang
memuat `Selvi\Tests\Controllers\...` dari `tests/app/` dan berjalan di
container yang sama.
