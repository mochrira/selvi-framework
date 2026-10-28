# Selvi Framework
⚡ Framework PHP super cepat untuk membangun API

## Mulai dengan Selvi

Tutorial ini membuat aplikasi sederhana dengan satu route `/` yang ditangani
oleh function closure. Tidak perlu membuat kelas controller atau konfigurasi
database untuk mencobanya.

### Persiapan

- Apache dengan `mod_rewrite` aktif dan konfigurasi `AllowOverride All` untuk
  direktori aplikasi, supaya aturan `.htaccess` dapat digunakan.
- PHP 8.1 atau lebih baru.
- Composer untuk menginstal Selvi Framework dan dependensinya.

### 1. Siapkan proyek

Buat direktori proyek di document root Apache, misalnya `htdocs/selvi-app`,
lalu jalankan perintah berikut dari direktori tersebut:

```sh
composer require mochrira/selvi-framework
```

### 2. Buat route dan jalankan framework

Buat file `index.php` di direktori proyek:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

\Selvi\Routing\Route::get('/', function () {
    return response('Halo, Selvi Framework!');
});

\Selvi\Framework::run();
```

Route `GET /` di atas menggunakan closure sebagai controller. Helper
`response()` mengirimkan teks sebagai respons HTTP.

### 3. Arahkan request ke `index.php`

Buat file `.htaccess` di direktori proyek:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [L]
```

Pastikan Apache mengizinkan penggunaan `.htaccess` pada direktori tersebut.
Buka `http://localhost/selvi-app/` di browser; halaman akan menampilkan
`Halo, Selvi Framework!`.