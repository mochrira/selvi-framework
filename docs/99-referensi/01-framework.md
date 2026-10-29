# Referensi: `Selvi\Framework`

Kelas statis. Tidak ada instance; seluruh pemakaian lewat method statis.
Contoh pemakaian ada di [instalasi](../01-memulai/01-instalasi.md).

## Method statis

### `run()`

Menjalankan siklus request dari pencocokan route sampai pengiriman respons.

**Kembalian:** `void`. Proses berakhir di dalam `send()` milik objek
`Response` (`die()`), jadi kode setelah pemanggilan tidak pernah dijalankan.

#### Perilaku

| Tahap | Yang terjadi |
|---|---|
| 1 | Bila `php_sapi_name() === 'cli'`, dialihkan ke `Cli::listen()` lalu berhenti |
| 2 | `Request` dan `Uri` dibuat lewat `Factory` (satu instance per proses) |
| 3 | `Router::resolve($method, $uri)` mencocokkan route |
| 4 | Tidak ada yang cocok → `HttpException` 404 |
| 5 | Route aktif disimpan di request (`$request->route()`) |
| 6 | Callback di-resolve lewat `Injector`; middleware dibungkus |
| 7 | Hasil callback dikirim lewat `send()` pada objek `Response` |

#### Aturan pakai

- Route harus didaftarkan **sebelum** `run()`; `Router` hanya array statis.
- Callback wajib mengembalikan `Response` (`response()`, `jsonResponse()`,
  `view(...)->render()`).
- Agar error tampil sebagai JSON, daftarkan handler lebih dulu:
  `Selvi\Exception\Handler::setDefaultHandlers()` lalu `Handler::listen()`.

#### Batasan

- Cabang CLI membaca `$argv[1]` tanpa pemeriksaan; menjalankan entry point tanpa
  argumen akan gagal.
- `Uri` membutuhkan `$_SERVER['SERVER_NAME']`, `SERVER_PORT`, `REQUEST_URI`, dan
  `SCRIPT_NAME`, jadi hanya berjalan di bawah web server.
- `header()` pada objek `Request` memakai `apache_request_headers()`.

## Sumber

- `src/Framework.php` — implementasi.
- `src/Router.php`, `src/Injector.php`, `src/Output/Response.php` — komponen
  yang dipanggil.
- `tests/01.01-instalasi/index.php`, `tests/01.02-response/index.php`,
  `tests/01.03-request/index.php` — aplikasi uji.

## Status verifikasi

Jalur web (tahap 2–7) terverifikasi lewat tiga aplikasi uji di atas: route
closure, teks, JSON, view, status code, serta pembacaan query, body, header,
cookie, dan file. Cabang CLI (tahap 1) **BELUM DIVERIFIKASI**.
