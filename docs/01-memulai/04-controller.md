---
id: controller
title: Controller
type: panduan
tags: [controller, dependency-injection, route-callback, middleware, base-class]
prerequisites: [response, request]
verified: 2026-11-05
applies_to: Selvi 3.x
---

# Controller

Closure cukup untuk route sederhana. Saat route bertambah, pindahkan logika ke
kelas controller dan tunjuk method-nya dari route dengan string
`Namespace\Kelas@method`.

## Jawaban singkat

```php
// app/Controllers/HaloController.php
namespace App\Controllers;

class HaloController {

    public function index() {
        return response('Halo dari controller!');
    }

}
```

```php
Route::get('/halo', 'App\Controllers\HaloController@index');
```

Hasil `GET /halo`: `200`, body `Halo dari controller!`. Instance dibuat
framework; dependency disuntikkan lewat constructor atau parameter method.

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
kemudian dimuat otomatis. Struktur folder yang dipakai halaman ini (`app/`,
`bootstrap.php`, `index.php`) dijelaskan di
[Struktur folder aplikasi](05-struktur-folder.md).

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

## 4. Dependency di parameter method dan helper `inject()`

Dependency juga bisa diminta langsung di parameter method controller. Urutan
resolusinya sama: nama parameter dicocokkan dengan parameter route lebih dulu,
lalu tipe kelas dibuat lewat `Factory`. Tambahkan method berikut pada
`HaloController` yang sama:

```php
public function methodInject(SapaanService $sapaan, string $nama) {
    return response($sapaan->pesan($nama));
}
```

```php
Route::get('/method-inject/{nama}', 'App\Controllers\HaloController@methodInject');
```

```sh
curl http://localhost/selvi-app/method-inject/Budi
# Halo, Budi!
```

Helper `inject()` meminta instance yang sama dengan yang dipakai constructor,
karena `Factory` menyimpan satu instance per kelas dalam satu request:

```php
public function injectHelper(string $nama) {
    $sapaan = inject(SapaanService::class);
    return jsonResponse([
        'pesan' => $sapaan->pesan($nama),
        'samaDenganConstructor' => $sapaan === $this->sapaan
    ]);
}
```

```php
Route::get('/inject/{nama}', 'App\Controllers\HaloController@injectHelper');
```

```sh
curl http://localhost/selvi-app/inject/Budi
```

```json
{
    "pesan": "Halo, Budi!",
    "samaDenganConstructor": true
}
```

| Cara | Kapan dipakai |
|---|---|
| Constructor | Dependency dipakai hampir semua method controller. |
| Parameter method | Dependency hanya dibutuhkan satu method. |
| `inject()` | Dependency dibutuhkan di tengah method, atau route memakai callback array yang instancenya dibuat sendiri. |

Parameter method wajib punya type hint atau default value. Parameter tanpa type
hint gagal di-resolve dengan `Call to a member function isBuiltin() on null`.

## 5. Bentuk callback route

| Bentuk | Contoh | Keterangan |
|---|---|---|
| String | `'App\Controllers\HaloController@index'` | Direkomendasikan; instance dibuat framework. |
| Array | `[$controller, 'index']` | Instance dibuat sendiri, mis. `[inject(HaloController::class), 'index']`. |
| Closure | `function () { return response('Halo'); }` | Cocok untuk route sangat sederhana. |

Pada callback array, dependency constructor menjadi tanggung jawab Anda.
Parameter method tetap di-resolve framework seperti pada callback string.

Semua method HTTP memakai pola yang sama: `Route::get()`, `Route::post()`,
`Route::patch()`, `Route::delete()`, dan `Route::options()`.

## 6. Controller di dalam grup dan middleware

Middleware memasang logika sebelum controller dijalankan. Signature-nya
menerima satu parameter yang harus bernama `$next`; panggil `$next()` untuk
melanjutkan ke controller, atau kembalikan `Response` sendiri untuk
menghentikan rantai.

Middleware yang mengubah response hasil controller:

```php
Route::withMiddleware(function ($next) {
    $res = $next();
    $res->setContent($res->getContent().' + middleware');
    return $res;
}, function () {
    Route::get('/middleware', 'App\Controllers\HaloController@index');
});
```

```sh
curl http://localhost/selvi-app/middleware
# Halo dari controller! + middleware
```

Middleware yang menghentikan rantai tanpa menjalankan controller:

```php
Route::get('/middleware-route', 'App\Controllers\HaloController@index')
    ->setMiddleware(function ($next) {
        return response('Middleware route');
    });
```

```sh
curl http://localhost/selvi-app/middleware-route
# Middleware route
```

Controller juga bisa didaftarkan di dalam grup:

```php
Route::group(function () {
    Route::get('/grup', 'App\Controllers\HaloController@index');
});
```

- `Route::group()` tidak menambahkan prefix URI; URI route di dalam grup tetap
  ditulis lengkap.
- Middleware pada grup dipasang lewat `setMiddleware()` milik `RouteGroup`.
  Bentuk middleware dan penggabungannya dijelaskan di
  [`Selvi\Routing\Route`](../99-referensi/02-route.md).
- Controller tidak perlu mengetahui middleware; middleware menerima `Response`
  hasil controller dari `$next()`.

## 7. Menangani kesalahan di controller

Controller punya dua cara melaporkan kegagalan. Pilih berdasarkan apakah situasi
itu bagian normal dari kontrak API atau kesalahan tak terduga.

| Situasi | Cara | Status |
|---|---|---|
| Input tidak valid atau data tidak ditemukan | `throw new Selvi\Exception(...)` | `400`, `404`, `422` sesuai argumen |
| Kondisi yang tetap punya body sendiri | `return response(...)` / `jsonResponse(...)` | bebas |
| Bug atau exception tak terduga | biarkan naik ke handler | `500` |

Konstruktor `Selvi\Exception` menerima pesan, kode string, status HTTP, dan data
tambahan (`src/Exception.php`):

```php
public function __construct(
    string $message,
    ?string $codeString = null,
    int $error = 500,
    mixed $data = null
)
```

Contoh data tidak ditemukan memakai model `Produk` dari
[Panduan Endpoint CRUD API](../05-api/01-crud-endpoint.md); tambahkan
`use Selvi\Exception;` pada controller.

```php
private function find(string $id): Produk {
    if(!ctype_digit($id)) {
        throw new Exception('ID produk harus berupa angka.', 'produk/invalid-id', 400);
    }

    $produk = Produk::find((int) $id);
    if($produk === null) {
        throw new Exception('Produk tidak ditemukan.', 'produk/not-found', 404);
    }

    return $produk;
}
```

Hasil `GET /produk/999`:

```json
{
    "code": "produk/not-found",
    "message": "Produk tidak ditemukan."
}
```

Status `404`. Validasi mengumpulkan error per field, lalu menambahkannya dengan
`with()`:

```php
if($errors !== []) {
    throw (new Exception('Data produk tidak valid.', 'produk/invalid', 422))
        ->with('errors', $errors);
}
```

Hasil `PATCH /produk/1` dengan body tidak valid:

```json
{
    "code": "produk/invalid",
    "message": "Data produk tidak valid.",
    "data": {
        "errors": {
            "nama": "Nama wajib diisi.",
            "harga": "Harga wajib berupa bilangan bulat >= 0."
        }
    }
}
```

Status `422`.

- Bentuk JSON di atas berasal dari `Handler::setDefaultHandlers()` dan
  `Handler::listen()` yang dipanggil di bootstrap. Tanpa keduanya, exception
  tampil sebagai halaman error PHP.
- Handler `default` menyertakan `trace`. Di produksi, daftarkan handler sendiri
  lewat `Handler::set()` agar detail internal tidak keluar ke klien.
- Untuk `204` tanpa body, gunakan `jsonResponse(null, 204)`.

## Referensi API

| Simbol | Signature | Keterangan |
|---|---|---|
| `Route::get()` | `Route::get(string $uri, callable\|string\|array $callback): Route` | Juga tersedia `post()`, `patch()`, `delete()`, `options()`. |
| `Injector::resolve()` | `resolve(mixed $callback, array $knownParams = []): array` | Mengubah `Kelas@method`/array menjadi callable dan daftar parameter; nama parameter dipakai lebih dulu, lalu tipe. |
| `Factory::resolve()` | `resolve(string $className, array $knownParams = []): ?object` | Membuat instance kelas beserta constructor dependency-nya; satu instance per kelas. |
| `inject()` | `inject(string $className): ?object` | Alias `Factory::resolve()` untuk meminta dependency dari mana saja. |
| `Selvi\Exception` | `new Exception(string $message, ?string $codeString = null, int $error = 500, mixed $data = null)` | Exception framework; `with($name, $value)` menambah data yang ikut dikirim handler. |
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
| `Call to a member function isBuiltin() on null` | Parameter method/constructor tidak punya type hint | beri type hint atau default value |
| Controller tidak dijalankan | Middleware tidak memanggil `$next()` | panggil `$next()` atau kembalikan `Response` sendiri |
| Exception menjadi halaman HTML, bukan JSON | `Handler::setDefaultHandlers()` dan `Handler::listen()` belum dipanggil | panggil keduanya di `bootstrap.php` |

## Sumber

- [src/Routing/Route.php](../../src/Routing/Route.php) — pendaftaran route dan format callback.
- [src/Router.php](../../src/Router.php) — pencocokan method dan URI.
- [src/Injector.php](../../src/Injector.php) — pemecahan `Kelas@method` dan resolusi parameter.
- [src/Factory.php](../../src/Factory.php) — pembuatan instance controller dan dependency.
- [src/Helpers/LoadHelper.php](../../src/Helpers/LoadHelper.php) — helper `inject()`.
- [src/Exception.php](../../src/Exception.php) — `Selvi\Exception`, `with()`, dan kode string.
- [src/Exception/Handler.php](../../src/Exception/Handler.php) — bentuk JSON error dari handler bawaan.
- [src/Framework.php](../../src/Framework.php) — pemanggilan controller dan `send()`.
- [src/Controller.php](../../src/Controller.php) dan [src/Base.php](../../src/Base.php) — base class opsional.
- [tests/01.04-controller/index.php](../../tests/01.04-controller/index.php),
  [tests/01.04-controller/Controllers/HaloController.php](../../tests/01.04-controller/Controllers/HaloController.php), dan
  [tests/01.04-controller/Services/SapaanService.php](../../tests/01.04-controller/Services/SapaanService.php) — semua contoh di halaman
  ini.

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 5 November 2026 dengan PHP 8.4.14 lewat
`http://localhost:8089/01.04-controller/`. Aplikasi uji memuat `vendor/`
workspace, jadi yang diuji adalah `src/` repo ini. Folder uji memuat kelas
`App\` secara manual karena autoload repo hanya memetakan `Selvi\` dan
`Selvi\Tests\`; aplikasi nyata memakai PSR-4 seperti pada Prasyarat.

| Endpoint | Status | Hasil |
|---|---|---|
| `GET /` | `200` | `Halo dari controller!` |
| `GET /sapa/Budi` | `200` | `Halo, Budi!` |
| `GET /method-inject/Budi` | `200` | `Halo, Budi!` |
| `GET /inject/Budi` | `200` | JSON `pesan` dan `samaDenganConstructor` bernilai `true` |
| `GET /array-callback/Budi` | `200` | `Halo, Budi!` |
| `GET /grup` | `200` | `Halo dari controller!` |
| `GET /middleware` | `200` | `Halo dari controller! + middleware` |
| `GET /middleware-route` | `200` | `Middleware route` |
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

Bagian 7 (penanganan kesalahan) tidak diuji lewat folder ini karena
`tests/01.04-controller/index.php` tidak mendaftarkan handler exception. Bentuk
JSON `400`/`404`/`422` pada bagian 7 diverifikasi lewat
`tests/05.01-crud-endpoint/`, yang memanggil `Handler::setDefaultHandlers()`
dan `Handler::listen()` di `bootstrap.php`.

Belum diuji: middleware berbentuk string `'Kelas@method'`/array dan grup
bersarang (`Route::group()` di dalam grup). Halaman ini hanya memakai closure
dan satu tingkat grup.
