# Controller

Closure cukup untuk route sederhana. Saat route bertambah, pindahkan logika ke
kelas controller dan tunjuk method-nya dari route dengan string
`Namespace\Kelas@method`.

## Prasyarat

- [Instalasi](01-instalasi.md) sudah berhasil.
- [Response](02-response.md) dan [Request](03-request.md) sudah dibaca.
- Namespace aplikasi terdaftar di autoload Composer. Tambahkan pemetaan
  berikut pada bagian `autoload.psr-4` di `composer.json` aplikasi, atau
  gabungkan bila bagian itu sudah ada:

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "app/"
        }
    }
}
```

Setelah itu jalankan `composer dump-autoload`. File
`app/Controllers/HaloController.php` dengan namespace `App\Controllers`
kemudian dimuat otomatis.

## 1. Controller pertama

`app/Controllers/HaloController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

class HaloController {

    public function index() {
        return response('Halo dari controller!');
    }

}
```

`index.php`:

```php
<?php

require __DIR__.'/vendor/autoload.php';

use Selvi\Routing\Route;

Route::get('/', 'App\Controllers\HaloController@index');

\Selvi\Framework::run();
```

Hasil `GET /`:

```text
Halo dari controller!
```

- Pemisah kelas dan method adalah `@`, bukan `::`.
- Framework membuat instance controller sendiri, lalu memanggil method-nya.
- Controller tidak wajib mewarisi kelas apa pun. `Selvi\Controller` tersedia
  sebagai base kosong bila ingin penamaan yang seragam.

## 2. Menambah method dan parameter route

```php
public function sapa(string $nama) {
    return response("Halo, {$nama}!");
}
```

```php
Route::get('/sapa/{nama}', 'App\Controllers\HaloController@sapa');
```

```sh
curl http://localhost/selvi-app/sapa/Budi
# Halo, Budi!
```

Nama parameter method harus sama dengan nama placeholder. Pencocokan memakai
nama, bukan urutan:

```php
public function urut(string $pertama, string $kedua) {
    return response("{$pertama}|{$kedua}");
}
```

```php
Route::get('/urut/{kedua}/{pertama}', 'App\Controllers\HaloController@urut');
```

```sh
curl http://localhost/selvi-app/urut/A/B
# B|A
```

Nilai route selalu berupa string. Untuk angka, deklarasikan `string` lalu
validasi dan konversi sendiri:

```php
public function angka(string $id) {
    if (!ctype_digit($id)) {
        return response('ID harus angka', 400);
    }
    return jsonResponse(['id' => (int) $id]);
}
```

```php
Route::get('/angka/{id}', 'App\Controllers\HaloController@angka');
```

```sh
curl http://localhost/selvi-app/angka/5
```

```json
{
    "id": 5
}
```

```sh
curl -i http://localhost/selvi-app/angka/abc
# HTTP/1.1 400 Bad Request
# ID harus angka
```

## 3. Menyuntikkan dependency lewat constructor

Parameter constructor di-resolve otomatis berdasarkan type hint. Tidak ada
pendaftaran service seperti pada container DI.

`app/Services/SapaanService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services;

class SapaanService {

    public function pesan(string $nama): string {
        return "Halo, {$nama}!";
    }

}
```

`app/Controllers/HaloController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\SapaanService;
use Selvi\Input\Request;

class HaloController {

    public function __construct(
        private Request $request,
        private SapaanService $sapaan
    ) { }

    public function sapa(string $nama) {
        return response($this->sapaan->pesan($nama));
    }

    public function cari() {
        $q = $this->request->get('q');
        return jsonResponse(['q' => $q]);
    }

}
```

```php
Route::get('/sapa/{nama}', 'App\Controllers\HaloController@sapa');
Route::get('/cari', 'App\Controllers\HaloController@cari');
```

```sh
curl http://localhost/selvi-app/sapa/Budi
# Halo, Budi!

curl 'http://localhost/selvi-app/cari?q=selvi'
```

```json
{
    "q": "selvi"
}
```

- `Request` menyediakan query string, body, header, dan file seperti pada
  halaman [Request](03-request.md).
- `SapaanService` tidak perlu didaftarkan; framework membuatnya karena
  constructor meminta tipe tersebut.
- Instance controller dan dependency-nya dipakai ulang dalam satu request.

## 4. Bentuk callback route

| Bentuk | Contoh | Keterangan |
|---|---|---|
| String | `'App\Controllers\HaloController@index'` | Direkomendasikan; instance dibuat framework. |
| Array | `[$controller, 'index']` | Instance dibuat sendiri; dependency harus disiapkan manual. |
| Closure | `function () { return response('Halo'); }` | Cocok untuk route sangat sederhana. |

Semua method HTTP memakai pola yang sama: `Route::get()`, `Route::post()`,
`Route::patch()`, `Route::delete()`, dan `Route::options()`.

## Referensi API

| Simbol | Signature | Keterangan |
|---|---|---|
| `Route::get()` | `Route::get(string $uri, callable\|string\|array $callback): Route` | Juga tersedia `post()`, `patch()`, `delete()`, `options()`. |
| `Injector::resolve()` | `resolve(mixed $callback, array $knownParams = []): array` | Mengubah `Kelas@method` menjadi callable dan daftar parameter. |
| `Factory::resolve()` | `resolve(string $className, array $knownParams = []): ?object` | Membuat instance kelas beserta constructor dependency-nya. |
| `Selvi\Controller` | `class Controller extends Base {}` | Base class kosong; opsional. |

Rincian method ada di [`Selvi\Routing\Route`](../99-referensi/02-route.md) dan
[`Selvi\Controller`](../99-referensi/07-controller.md). `Injector` dan `Factory`
dipakai internal; alurnya dijelaskan di
[`Framework::run()`](../99-referensi/01-framework.md).

## Kesalahan umum

| Gejala | Penyebab | Perbaikan |
|---|---|---|
| `Class "App\Controllers\TidakAda" does not exist` | Namespace tidak cocok dengan path, atau `App\` belum dipetakan di autoload | samakan namespace dengan PSR-4, lalu `composer dump-autoload` |
| `Argument #1 ($nama) must be of type string, null given` | Nama parameter method berbeda dari placeholder route | samakan, mis. `{nama}` dengan `$nama` |
| `Argument #1 ($id) must be of type int, string given` | Nilai route berupa string dan URL berisi non-angka | deklarasikan `string`, lalu validasi dan cast |
| `Call to a member function send() on null` | Method controller tidak mengembalikan `Response` | tambahkan `return response(...)` atau `return jsonResponse(...)` |
| `Route GET '/...' not found` | Route belum didaftarkan atau method HTTP tidak cocok | daftarkan route dengan method HTTP yang benar |

## Sumber

- `src/Routing/Route.php` — pendaftaran route dan format callback.
- `src/Router.php` — pencocokan method dan URI.
- `src/Injector.php` — pemecahan `Kelas@method` dan resolusi parameter.
- `src/Factory.php` — pembuatan instance controller dan dependency.
- `src/Framework.php` — pemanggilan controller dan `send()`.
- `src/Controller.php` dan `src/Base.php` — base class opsional.
- `tests/01.04-controller/index.php`,
  `tests/01.04-controller/Controllers/HaloController.php`, dan
  `tests/01.04-controller/Services/SapaanService.php` — semua contoh di halaman
  ini.

## Status verifikasi

Diuji 29 Oktober 2026 dengan PHP 8.4.14 lewat
`http://localhost:8089/01.04-controller/`. Aplikasi uji memuat `vendor/`
workspace, jadi yang diuji adalah `src/` repo ini. Folder uji memuat kelas
`App\` secara manual karena autoload repo hanya memetakan `Selvi\` dan
`Selvi\Tests\`; aplikasi nyata memakai PSR-4 seperti pada Prasyarat.

| Endpoint | Status | Hasil |
|---|---|---|
| `GET /` | `200` | `Halo dari controller!` |
| `GET /sapa/Budi` | `200` | `Halo, Budi!` |
| `GET /cari?q=selvi` | `200` | JSON `q` berisi `selvi` |
| `GET /cari` | `200` | JSON `q` berisi `null` |
| `GET /urut/A/B` | `200` | `B\|A` |
| `GET /angka/5` | `200` | JSON `id` berisi `5` |
| `GET /angka/abc` | `400` | `ID harus angka` |
| `GET /tipe-angka/5` | `200` | JSON `id` berisi `5` |
| `GET /tipe-angka/abc` | `500` | sengaja salah: type hint `int` menolak string non-angka |
| `GET /salah-nama/Budi` | `500` | sengaja salah: nama parameter tidak cocok |
| `GET /tanpa-return` | `500` | body `Halo tanpa return`, lalu error karena tidak ada `Response` |
| `GET /kelas-salah` | `500` | sengaja salah: kelas controller tidak ada |
