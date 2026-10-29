---
id: ref-route
title: Selvi\Routing\Route
type: referensi
class: Selvi\Routing\Route
tags: [route, http-method, route-group, middleware, parameter-uri]
prerequisites: [controller]
verified: 2026-10-29
applies_to: Selvi 3.x
---

# Referensi: `Selvi\Routing\Route`

Objek route. Instance dibuat lewat method statis `get()`, `post()`, `patch()`,
`delete()`, atau `options()`, lalu disimpan di registry statis `Selvi\Router`.
Route harus didaftarkan sebelum `Framework::run()`.

Contoh pemakaian ada di [instalasi](../01-memulai/01-instalasi.md) dan
[controller](../01-memulai/04-controller.md).

Aturan yang berlaku untuk semua route:

- Method HTTP harus sama persis, mis. `GET` atau `POST`.
- Placeholder `{nama}` dicocokkan dengan regex `(.+)`; route tidak memvalidasi
  tipe nilai.
- Route pertama yang cocok yang dipakai; `Router::resolve()` berhenti pada
  hasil pertama.
- Nilai placeholder diteruskan ke callback berdasarkan **nama** parameter
  (lihat [`Framework::run()`](01-framework.md)).

## Method statis

### `Route::get($uri, $callback)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$uri` | `string` | wajib | pola URI, mis. `/kontak/{id}` |
| `$callback` | `Closure\|array\|string` | wajib | closure, `[objek, 'method']`, atau `'Kelas@method'` |

**Kembalian:** `Route`.

### `Route::post($uri, $callback)`

Sama seperti `get()`, tetapi hanya mencocokkan method `POST`.

**Kembalian:** `Route`.

### `Route::patch($uri, $callback)`

Sama seperti `get()`, tetapi hanya mencocokkan method `PATCH`.

**Kembalian:** `Route`.

### `Route::delete($uri, $callback)`

Sama seperti `get()`, tetapi hanya mencocokkan method `DELETE`.

**Kembalian:** `Route`.

### `Route::options($uri, $callback)`

Sama seperti `get()`, tetapi hanya mencocokkan method `OPTIONS`.

**Kembalian:** `Route`.

### `Route::group($callback)`

Mendaftarkan beberapa route sebagai satu grup.

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$callback` | `Closure` | wajib | mendaftarkan route di dalamnya |

**Kembalian:** `RouteGroup`.

Catatan:

- Tidak ada prefix URI otomatis; URI route di dalam grup tetap ditulis lengkap.
- Grup dapat disarangkan.
- Middleware dan parameter dapat ditambahkan pada grup lewat
  `setMiddleware()` dan `setParam()`.

### `Route::withMiddleware($middleware, $callback)`

Membuat grup dan langsung memasang middleware.

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$middleware` | `Closure\|array\|string` | wajib | closure, `[objek, 'method']`, atau `'Kelas@method'` |
| `$callback` | `Closure` | wajib | mendaftarkan route di dalamnya |

**Kembalian:** `RouteGroup`.

Middleware menerima parameter `$next`. Panggil `$next()` untuk menjalankan
route berikutnya, atau kembalikan `Response` sendiri untuk menghentikan rantai.

## Method instance

### `Route::getMethod()`

**Kembalian:** `string` — method HTTP route, mis. `GET`.

### `Route::getUri()`

**Kembalian:** `string` — pola URI yang didaftarkan.

### `Route::getCallback()`

**Kembalian:** `Closure|array|string` — callback asli route.

### `Route::params()`

**Kembalian:** `array` — seluruh parameter route, termasuk parameter URI,
parameter grup, dan parameter yang ditambahkan lewat `setParam()`. Nilai dari
URI berupa `string`.

### `Route::getParam($name)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | wajib | nama parameter |

**Kembalian:** `mixed` — nilai parameter, atau `null` bila tidak ada.

### `Route::setParam($name, $value)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | wajib | nama parameter |
| `$value` | `mixed` | wajib | nilai parameter |

**Kembalian:** `RouteInterface` — instance route sendiri.

### `Route::getMiddleware()`

**Kembalian:** `array` — daftar middleware route.

### `Route::setMiddleware($middleware)`

Menambahkan middleware ke daftar yang sudah ada.

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$middleware` | `Closure\|array\|string` | wajib | satu atau beberapa middleware |

**Kembalian:** `RouteInterface` — instance route sendiri.

### `Route::match($method, $uri, $parent)`

Mencocokkan route dengan request. Dipakai internal oleh `Router`.

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$method` | `string` | wajib | method HTTP request |
| `$uri` | `string` | wajib | URI request |
| `$parent` | `?RouteGroup` | `null` | grup induk untuk pewarisan middleware/parameter |

**Kembalian:** `Route|false` — route hasil kompilasi, atau `false` bila tidak
cocok.

Catatan: pola URI tidak di-escape sebelum dijadikan regex, sehingga karakter
khusus regex pada URI ikut berlaku. Gunakan URI sederhana.

### `Route::compile($parent)`

Membuat salinan route dengan middleware dan parameter dari grup induk.

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$parent` | `?RouteGroup` | `null` | grup induk |

**Kembalian:** `Route` — salinan route yang siap dijalankan.

## Sumber

- `src/Routing/Route.php` — implementasi.
- `src/Routing/RouteGroup.php` — grup, middleware, dan parameter grup.
- `src/Routing/RouteInterface.php` — kontrak route dan grup.
- `src/Router.php` — registry dan pencocokan route.
- `tests/01.04-controller/index.php` — aplikasi uji method statis, grup, dan
  middleware.
- `tests/01.03-request/index.php` — aplikasi uji `getUri()`.

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 29 Oktober 2026 (PHP 8.4.14) lewat
`http://localhost:8089/01.04-controller/` dan
`http://localhost:8089/01.03-request/`:

| Method | Kasus uji | Hasil |
|---|---|---|
| `get()` | route `/` dan `/grup` | `200` |
| `post()` | `POST /01.03-request/kontak` | `201` |
| `patch()` | `PATCH /01.04-controller/metode` | `200`, body `PATCH` |
| `delete()` | `DELETE /01.04-controller/metode` | `200`, body `DELETE` |
| `options()` | `OPTIONS /01.04-controller/metode` | `200`, body `OPTIONS` |
| `group()` | `GET /01.04-controller/grup` | `200` |
| `withMiddleware()` | `GET /01.04-controller/middleware` | body ditambah middleware |
| `setMiddleware()` | `GET /01.04-controller/middleware-route` | `200`, body `Middleware route` |
| `getMethod()` | `GET /01.04-controller/info-route/Budi` | `GET` |
| `getUri()` | `GET /01.04-controller/info-route/Budi` | `/info-route/{nama}` |
| `getCallback()` | `GET /01.04-controller/info-route/Budi` | `App\Controllers\HaloController@infoRoute` |
| `params()` + `getParam()` | `GET /01.04-controller/info-route/Budi` | `nama` berisi `Budi` |
| `setParam()` | `GET /01.04-controller/param` | `Nilai Param` |
| `match()` + `compile()` | seluruh route di atas | terpakai tidak langsung |

Belum diuji langsung: `getMiddleware()` dan bentuk array/string pada
`setMiddleware()`. Keduanya dijelaskan dari kode sumber. `getUri()` juga
terverifikasi lewat `GET /01.03-request/route-info`.
