# Siklus Request

Halaman inti dokumentasi. Semua fitur lain menempel pada urutan yang dijelaskan
di sini: route didaftarkan, request dicocokkan, dependency disuntikkan,
middleware membungkus controller, lalu `Response` dikirim.

## Prasyarat

- [Instalasi](01-instalasi.md)
- [Struktur proyek](02-struktur-proyek.md)

> Saat pembacaan pertama, cukup sampai bagian **Normalisasi URI**. Bagian
> **Middleware** dan **Perilaku error** boleh dilewati.

## Ringkasan

Satu request melewati tujuh tahap:

```text
index.php
  Route::get('/', ...)        → Router::$routes[]        (tahap 1: daftar)
  Framework::run()                                        (tahap 2)
     ├─ CLI? → Cli::listen() → send() → die()             (tahap 3)
     ├─ Factory::resolve(Request::class), (Uri::class)    (tahap 4)
     ├─ Router::resolve(method, uri)                      (tahap 5)
     ├─ Request::setRoute(route)                          (tahap 6)
     └─ Injector::resolve(callback + params)
           → middleware (dibungkus berurutan)
           → callback controller
           → Response::send()                             (tahap 7)
```

`Framework::run()` mengakhiri proses dengan `die()` di dalam `send()`.

## Tahap demi tahap

| # | Lokasi | Yang terjadi |
|---|---|---|
| 1 | `Route::get()` → `Router::add()` | Objek `Route` disimpan ke array statis `Router::$routes`. Urutan pendaftaran = urutan pencocokan. Belum ada eksekusi. |
| 2 | `Framework::run()` | Titik masuk tunggal untuk web dan CLI. |
| 3 | `php_sapi_name() == 'cli'` | Jika CLI, `Cli::listen()` dijalankan lalu proses berhenti. Semua logika web dilewati. |
| 4 | `Factory::resolve()` | `Request` dan `Uri` dibuat sekali per proses (singleton per kelas). `Uri` membaca `$_SERVER`. |
| 5 | `Router::resolve()` | Method harus sama persis; URI dicocokkan dengan regex. Route pertama yang cocok dipakai. Tidak ada yang cocok → `HttpException` 404. |
| 6 | `Request::setRoute()` | Route aktif disimpan agar controller bisa membaca `$request->route()`. |
| 7 | `Injector::resolve()` + `$action()->send()` | Callback di-resolve, middleware dibungkus, hasilnya `Response` dikirim: status code, `echo` isi, `die()`. |

## Kode sumber `run()`

Potongan berikut disalin dari `src/Framework.php` (hanya komentar yang
ditambahkan):

```php
public static function run(): void {
    if(php_sapi_name() == 'cli') {
        Cli::listen()->send();
        die();
    }

    $request = Factory::resolve(Request::class);
    $method = $request->method();

    $uri = Factory::resolve(Uri::class);
    $current_uri = $uri->string();

    $route = Router::resolve($method, $current_uri);
    if($route === false) throw new HttpException("Route ".$method." '".$current_uri."'  not found", 404, $method, $current_uri);

    $request->setRoute($route);
    $action = function () use ($route) {
        $ref = Injector::resolve($route->getCallback(), $route->params());
        return call_user_func($ref['cb'], ...$ref['params']);
    };

    foreach($route->getMiddleware() as $middleware) {
        $action = function () use ($middleware, $route, $action) {
            $params = $route->params();
            $params['next'] = $action;
            $ref = Injector::resolve($middleware, $params);
            return call_user_func($ref['cb'], ...$ref['params']);
        };
    }

    $action()->send();
}
```

## Kontrak yang harus dipegang

1. **Callback wajib mengembalikan `Response`.** `$action()->send()` dipanggil
   pada nilai balik callback. `echo` lalu `return null` menghasilkan
   `Error: Call to a member function send() on null`.
2. **Nama parameter callback harus sama dengan placeholder URI.** Untuk route
   `/kontak/{idKontak}`, gunakan `function (string $idKontak)`.
3. **Nama parameter middleware `next` tidak boleh diganti.** `Injector`
   mencocokkan berdasarkan nama lebih dulu; nama lain akan dicoba di-resolve
   sebagai kelas dan gagal.
4. **Jangan menaruh kode setelah `Framework::run()`.** `send()` memanggil
   `die()`.
5. **Route harus didaftarkan sebelum `run()`.** `Router` hanya array statis;
   tidak ada mekanisme penambahan route setelah proses berjalan.

## Resolusi parameter (`Injector`)

Untuk setiap parameter callback, urutan pencarian:

1. **Nama** parameter ada di `route->params()` (dari placeholder URI atau
   `setParam()`) → pakai nilai itu.
2. **Tipe kelas** (mis. `Request`, service) → `Factory::resolve()`, singleton
   per kelas.
3. Tipe builtin tanpa nilai → `Injector` mengisi `null`; bila parameter
   non-nullable, pemanggilan callback gagal dengan `TypeError`.
4. Ada default value → pakai default.
5. **Tanpa type hint** → error, karena kode memanggil `getType()->isBuiltin()`
   pada `null`.

| Route | Callback | Hasil |
|---|---|---|
| `/kontak/{idKontak}` | `function (string $idKontak)` | `"12"` dari URI |
| `/kontak/{id}` | `function (int $idKontak)` | `TypeError` — nama tidak cocok, tipe builtin tanpa default |
| `/kontak` | `function (Request $request)` | instance `Request` |
| `/kontak` | `function ($bebas)` | error |

Cast manual tetap tanggung jawab controller, misalnya
`(int) $idKontak` seperti di `tests/app/Controllers/KontakController.php`.

## Normalisasi URI

`Uri::string()` membuang query string, garis miring berlebih, dan subdirektori
tempat framework dipasang:

| `REQUEST_URI` | `SCRIPT_NAME` | Hasil `Uri::string()` |
|---|---|---|
| `/` | `/index.php` | `/` |
| `/kontak` | `/index.php` | `/kontak` |
| `/kontak/` | `/index.php` | `/kontak` |
| `/kontak?limit=10` | `/index.php` | `/kontak` |
| `/selvi-app/kontak` | `/selvi-app/index.php` | `/kontak` |

Karena garis miring akhir dibuang, route `/kontak` cocok untuk `/kontak`
maupun `/kontak/`.

## Middleware

Middleware adalah closure (bukan kelas), dijalankan sebelum callback controller:

```php
use Selvi\Input\Request;
use Selvi\Routing\Route;

Route::withMiddleware(function (Request $request, Closure $next) {
    if ($request->header('Authorization') === null) {
        return jsonResponse(['message' => 'Unauthorized'], 401);
    }
    return $next();
}, function () {
    Route::get('/rahasia', 'App\\Controllers\\RahasiaController@index');
});
```

Aturan:

- Middleware menerima parameter route + `next`; harus memanggil `$next()` dan
  **mengembalikan** hasilnya.
- Middleware boleh menghentikan rantai dengan mengembalikan `Response` sendiri.
- Urutan eksekusi adalah kebalikan urutan di array: pada
  `setMiddleware([a, b])`, `b` berjalan lebih dulu. Middleware group ditambahkan
  setelah middleware route saat `compile()`, sehingga **middleware group
  berjalan sebelum middleware milik route**.
- `Route::group()` hanya mengelompokkan middleware dan parameter; **tidak ada
  prefix URI**.

## Perilaku error

| Situasi | Hasil |
|---|---|
| Tidak ada route yang cocok | `HttpException` dengan code string `http/error`, status 404, berisi `request.uri` dan `request.method` |
| Exception dilempar controller | Ditangani handler yang terdaftar |
| Handler belum didaftarkan | Fatal error PHP, bukan JSON |

Handler dipilih berdasarkan **nama kelas exception yang persis sama** dengan
tipe parameter handler. Exception turunan tidak otomatis memakai handler
induknya; jika tidak ada yang cocok, dipakai handler `default` (status 500,
menyertakan `trace`). Karena itu, exception kustom sebaiknya ditangani dengan
`Handler::set()` sendiri.

Contoh respons 404 dengan handler bawaan:

```json
{
    "code": "http\/error",
    "message": "Route GET '\/tidak-ada'  not found",
    "request": {
        "uri": "\/tidak-ada",
        "method": "GET"
    }
}
```

## Setelah response dikirim

`Response::send()` menjalankan `http_response_code($code)`, `echo $content`,
lalu `die()`. Konsekuensinya:

- Tidak ada kode yang berjalan setelahnya, termasuk middleware penutup.
- Framework tidak menutup koneksi database secara eksplisit; `mysqli` ditutup
  otomatis saat proses selesai.
- Tidak ada output buffering tambahan. Jika callback sudah mencetak sesuatu
  sebelum `return`, output itu ikut terkirim di luar `Response`.

## Jangan lakukan

| Kesalahan | Akibat |
|---|---|
| `echo 'ok'; return null;` | fatal error saat `send()` dipanggil |
| `return response('ok')` dari middleware tanpa `$next()` | controller tidak pernah berjalan (sah bila memang ingin menghentikan) |
| Memberi nama parameter middleware selain `next` | resolusi gagal |
| Mendaftarkan route di dalam callback | route tidak terlihat oleh request berikutnya (proses sudah selesai) |
| Mengandalkan `$_SERVER` di luar Apache | `Uri` butuh `SERVER_NAME`, `SERVER_PORT`, `REQUEST_URI` |

## Referensi API

| Simbol | Signature |
|---|---|
| `Router::resolve()` | `resolve(string $method, string $uri): RouteInterface\|false` |
| `Injector::resolve()` | `resolve(mixed $callback, array $knownParams = []): array{cb, params}` |
| `Request::route()` | `route(): Route` |
| `Response::send()` | `send(): void` |
| `Route::withMiddleware()` | `withMiddleware(Closure\|array\|string $middleware, Closure $callback): RouteGroup` |
| `Route::group()` | `group(Closure $callback): RouteGroup` |

## Sumber

- `src/Framework.php` — urutan lengkap.
- `src/Router.php` — registry dan pencocokan.
- `src/Routing/Route.php`, `src/Routing/RouteGroup.php` — `match()`, `compile()`,
  penggabungan middleware.
- `src/Injector.php`, `src/Factory.php` — resolusi parameter.
- `src/Input/Uri.php` — normalisasi URI.
- `src/Output/Response.php` — `send()`.
- `src/Exception/Handler.php` — pemilihan handler.
- `tests/index.php`, `tests/app/Config/routes.php`,
  `tests/app/Controllers/KontakController.php` — contoh berjalan.

## Status verifikasi

Alur dan kontrak diturunkan dari kode sumber. Yang **sudah terbukti berjalan**
adalah routing berbasis string controller + parameter `{metode}` +
`{idKontak}` di `tests/app/Config/routes.php`, termasuk 404 dari
`KontakController` yang melempar `Selvi\Exception`.

Yang **BELUM DIVERIFIKASI** karena tidak ada contohnya di `tests/`: middleware
(`withMiddleware`/`group`), pemanggilan `Cli::listen()` tanpa argumen, dan
route dengan callback closure di luar `tests/`. Uji dulu sebelum dijadikan
acuan.
