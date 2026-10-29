---
id: ref-request
title: Selvi\Input\Request
type: referensi
class: Selvi\Input\Request
tags: [request, header, cookie, body, file, query-string]
prerequisites: []
verified: 2026-10-28
applies_to: Selvi 3.x
---

# Referensi: `Selvi\Input\Request`

Objek pembaca data request. Instance disuntikkan otomatis ke callback bila
parameter diberi type hint `Request`. Tidak ada method statis.
`setRoute()` dipakai internal framework dan tidak perlu dipanggil.

## Method instance

### `Request::method()`

Mengambil method HTTP request.

**Kembalian:** `string` — mis. `GET`, `POST`.

### `Request::get($name)`

Membaca nilai dari query string.

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | wajib | nama parameter query |

**Kembalian:** `mixed` — nilai, atau `null` bila tidak dikirim.

### `Request::post($name)`

Membaca nilai dari body form-urlencoded atau multipart.

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | wajib | nama field form |

**Kembalian:** `mixed` — nilai, atau `null`. Body JSON **tidak** terbaca di
sini; gunakan `raw()`.

### `Request::raw()`

Membaca body request mentah.

**Kembalian:** `?string` — isi body, atau `null`.

### `Request::header($name)`

Membaca header request.

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `?string` | `null` | nama header; bila kosong, seluruh header dikembalikan |

**Kembalian:** `array|string|null` — nilai satu header, seluruh header, atau
`null` bila tidak ada.

Catatan:

- Memakai `apache_request_headers()`, jadi hanya tersedia pada SAPI Apache.
- Header `Authorization` dibuang Apache kecuali `.htaccess` menambahkan aturan
  passthrough.

### `Request::file($name)`

Membaca entri file upload.

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | wajib | nama field file |

**Kembalian:** `?array` — entri `$_FILES` (`name`, `type`, `tmp_name`,
`error`, `size`), atau `null`.

### `Request::cookie($name)`

Membaca nilai cookie.

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | wajib | nama cookie |

**Kembalian:** `mixed` — nilai cookie.

Catatan: membaca `$_COOKIE` langsung tanpa `isset`, sehingga cookie yang tidak
dikirim memunculkan `Warning: Undefined array key`.

### `Request::route()`

Mengambil route yang sedang dipakai.

**Kembalian:** `Selvi\Routing\Route` — route aktif, berguna untuk membaca
`getUri()`, `getMethod()`, atau parameter route.

## Sumber

- `src/Input/Request.php` — implementasi.
- `src/Injector.php` — penyuntikan berdasarkan type hint.
- `tests/01.03-request/index.php` — aplikasi uji.

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 28 Oktober 2026 (PHP 8.4.14) lewat
`http://localhost:8089/01.03-request/`:

| Method | Kasus uji | Hasil |
|---|---|---|
| `get()` | `?nama=Budi` | `Budi` |
| `get()` | tanpa parameter | `null` |
| `raw()` | body JSON | JSON terurai |
| `post()` | form `nama=Budi` | `Budi` |
| `header('X-Token')` | header dikirim | `abc123` |
| `header()` | tanpa argumen | array seluruh header |
| `header('Authorization')` | dengan aturan rewrite | terbaca |
| `file()` | upload `berkas` | `nama` dan `ukuran` terisi |
| `cookie()` | cookie `sesi=abc123` | `abc123` |
| `method()` | GET | `GET` |
| `route()` | `->getUri()` | `/route-info` |
