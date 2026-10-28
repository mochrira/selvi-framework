# Request

Data request diambil lewat `Selvi\Input\Request`. Objek ini disuntikkan
otomatis jika parameter callback diberi type hint `Request`.

## Prasyarat

- [Instalasi](01-instalasi.md) sudah berhasil.
- [Response](02-response.md) sudah dibaca.

## 1. Query string

```php
use Selvi\Input\Request;
use Selvi\Routing\Route;

Route::get('/halo', function (Request $request) {
    $nama = $request->get('nama') ?? 'Pengunjung';
    return response("Halo, {$nama}!");
});
```

```sh
curl 'http://localhost/selvi-app/halo?nama=Budi'
# Halo, Budi!

curl 'http://localhost/selvi-app/halo'
# Halo, Pengunjung!
```

`get()` mengembalikan `null` bila parameter tidak dikirim, sehingga perlu nilai
default.

## 2. Body JSON

```php
Route::post('/kontak', function (Request $request) {
    $data = json_decode($request->raw() ?? '', true) ?? [];
    return jsonResponse(['diterima' => $data], 201);
});
```

```sh
curl -X POST -H 'Content-Type: application/json' \
  -d '{"nama":"Budi","umur":30}' \
  http://localhost/selvi-app/kontak
```

```json
{
    "diterima": {
        "nama": "Budi",
        "umur": 30
    }
}
```

Gunakan `raw()` untuk body JSON. `post()` hanya membaca form-urlencoded.

## 3. Form

```php
Route::post('/form', function (Request $request) {
    return response('Halo, '.$request->post('nama'));
});
```

```sh
curl -X POST -d 'nama=Budi' http://localhost/selvi-app/form
# Halo, Budi
```

## 4. Header

```php
Route::get('/token', function (Request $request) {
    return response($request->header('X-Token') ?? 'tidak ada');
});
```

```sh
curl -H 'X-Token: abc123' http://localhost/selvi-app/token
# abc123
```

`header()` membaca header lewat `apache_request_headers()`, jadi hanya bekerja
di bawah Apache. Header `Authorization` dibuang Apache kecuali `.htaccess`
menambahkan aturan berikut:

```apache
RewriteCond %{HTTP:Authorization} ^(.*)
RewriteRule .* - [e=HTTP_AUTHORIZATION:%1]
```

## Referensi API

| Method | Kembalian |
|---|---|
| `get(string $name)` | nilai query string, atau `null` |
| `post(string $name)` | nilai form, atau `null` |
| `raw()` | body mentah (untuk JSON) |
| `header(?string $name = null)` | nilai satu header, atau semua header bila argumen kosong |
| `file(string $name)` | entri `$_FILES`, atau `null` |
| `cookie(string $name)` | nilai cookie |
| `method()` | method HTTP, mis. `GET` |
| `route()` | objek `Route` yang sedang dipakai |

## Kesalahan umum

| Gejala | Penyebab | Perbaikan |
|---|---|---|
| Body JSON selalu kosong dari `post()` | `post()` hanya membaca form | pakai `raw()` lalu `json_decode()` |
| `get('nama')` bernilai `null` | parameter tidak dikirim | beri default: `?? 'Pengunjung'` |
| `header('Authorization')` bernilai `null` | Apache membuang header itu | tambahkan aturan rewrite |
| `Warning: Undefined array key` pada `cookie()` | cookie tidak dikirim klien | pastikan cookie ada sebelum dipakai |

## Sumber

- `src/Input/Request.php` — seluruh method request.
- `src/Injector.php` — penyuntikan `Request` ke callback.
- `tests/01.03-request/index.php` — semua contoh di halaman ini.

## Status verifikasi

Diuji 28 Oktober 2026 dengan PHP 8.4.14 lewat
`http://localhost:8089/01.03-request/`. Aplikasi uji memuat `vendor/`
workspace, jadi yang diuji adalah `src/` repo ini.

| Endpoint | Status | Hasil |
|---|---|---|
| `GET /halo?nama=Budi` | `200` | `Halo, Budi!` |
| `GET /halo` | `200` | `Halo, Pengunjung!` |
| `POST /kontak` (JSON) | `201` | JSON `diterima` berisi data kiriman |
| `POST /form` (`nama=Budi`) | `200` | `Halo, Budi` |
| `GET /token` (`X-Token`) | `200` | `abc123` |
| `GET /token` tanpa header | `200` | `tidak ada` |
| `GET /header` (`Authorization`) | `200` | nilai header terbaca setelah aturan rewrite |
| `GET /metode` | `200` | `GET` |
| `GET /cookie` dengan cookie | `200` | `abc123` |
| `GET /cookie` tanpa cookie | `200` | `tidak ada`, plus warning di log |

Baris `cookie()` tanpa cookie memang memunculkan
`Warning: Undefined array key "sesi"` pada `src/Input/Request.php`; itu sebabnya
baris tersebut ada di tabel kesalahan umum.
