# Response

Setiap route harus mengembalikan `Response`. Ada dua helper: `response()`
untuk teks dan `jsonResponse()` untuk JSON.

## Prasyarat

- [Instalasi](01-instalasi.md) sudah berhasil.

## 1. Mengembalikan teks

```php
use Selvi\Routing\Route;

Route::get('/halo', function () {
    return response('Halo, Selvi Framework!');
});
```

Hasil: status `200`, body `Halo, Selvi Framework!`.

## 2. Mengembalikan JSON

```php
Route::get('/api/pesan', function () {
    return jsonResponse(['pesan' => 'Halo', 'kode' => 200]);
});
```

Hasil:

```json
{
    "pesan": "Halo",
    "kode": 200
}
```

- Parameter pertama harus `array` atau `null`.
- Output otomatis dirapikan (`JSON_PRETTY_PRINT`).

## 3. Halaman HTML (view)

`view()` memuat file template, lalu `render()` mengubahnya menjadi `Response`.
Template berisi HTML utuh, bukan potongan.

`views/home.php`:

```php
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($judul) ?></title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; background: #f5f5f5; color: #222; }
        main { max-width: 40rem; margin: 4rem auto; background: #fff; padding: 2rem; border-radius: .5rem; }
        h1 { margin-top: 0; }
    </style>
</head>
<body>
    <main>
        <h1><?= htmlspecialchars($judul) ?></h1>
        <p>Halo, <?= htmlspecialchars($nama) ?>!</p>
        <p>Halaman ini dirender oleh Selvi Framework.</p>
    </main>
</body>
</html>
```

`index.php`:

```php
use Selvi\Routing\Route;
use Selvi\View;

View::addPath(__DIR__.'/views');

Route::get('/halaman', function () {
    return view('home.php', [
        'judul' => 'Selvi Framework',
        'nama' => 'Pengunjung'
    ])->render();
});
```

Hasil: status `200` dengan halaman HTML lengkap.

- Nama file wajib memakai ekstensi (`home.php`, bukan `home`).
- `View::addPath()` menambah folder template. Folder bawaan framework
  (`src/views`) tetap tersedia.
- Variabel dari parameter kedua tersedia di template sebagai variabel biasa.

## 4. Mengatur status code

Status code adalah parameter kedua:

```php
Route::get('/tidak-ditemukan', function () {
    return response('Data tidak ditemukan', 404);
});

Route::get('/tanpa-isi', function () {
    return jsonResponse(null, 204);
});
```

`jsonResponse(null, 204)` mengirim status `204` tanpa body.

| Kebutuhan | Kode |
|---|---|
| Berhasil | `200` (default) |
| Data dibuat | `201` |
| Berhasil tanpa body | `204` |
| Tidak berhak | `401` |
| Tidak ditemukan | `404` |

## 5. Header `Content-Type`

Framework tidak mengatur header `Content-Type`, jadi Apache mengirim
`text/html` meskipun body-nya JSON. Untuk API, set sendiri sebelum `return`:

```php
Route::get('/api/pesan', function () {
    header('Content-Type: application/json');
    return jsonResponse(['pesan' => 'Halo']);
});
```

Sekarang responsnya berheader `Content-Type: application/json`.

## Referensi API

| Simbol | Signature |
|---|---|
| `response()` | `response(?string $content = '', int $code = 200): Response` |
| `jsonResponse()` | `jsonResponse(?array $data = null, int $code = 200, int $options = JSON_PRETTY_PRINT): JsonResponse` |
| `view()` | `view(string $file, array $vars = []): View` |
| `View::addPath()` | `addPath(string $path): void` |
| `View::render()` | `render(int $code = 200): Response` |
| `Response::cookie()` | `cookie(string $name, string $value = '', int $expire = 0, string $path = '', string $domain = '', bool $secure = false, bool $httponly = false): void` |
| `Response::send()` | `send(): void` — dipanggil framework, tidak perlu dipanggil sendiri |

`cookie()` mengirim cookie saat itu juga lewat `setcookie()`; `$expire` berupa
Unix timestamp.

## Kesalahan umum

| Gejala | Penyebab | Perbaikan |
|---|---|---|
| `TypeError` pada `jsonResponse('teks')` | parameter harus `array`/`null` | bungkus: `jsonResponse(['pesan' => 'teks'])` |
| Status `500`, body hanya teks dari `echo` | callback tidak mengembalikan `Response` | `return response(...)` |
| Client tidak mengenali JSON | header `Content-Type` masih `text/html` | set `header('Content-Type: application/json')` |
| Status `204` tapi masih ada body | data tidak `null` | `jsonResponse(null, 204)` |
| Status `500` dengan `TypeError: Cannot assign null to property Selvi\View::$file` | file template tidak ditemukan | pakai nama file lengkap (`home.php`) dan daftarkan folder lewat `View::addPath()` |

## Sumber

- `src/Helpers/ResponseHelper.php` — helper `response()` dan `jsonResponse()`.
- `src/Helpers/LoadHelper.php` — helper `view()`.
- `src/View.php` — pemuatan template dan `render()`.
- `src/Output/Response.php` — status code, cookie, `send()`.
- `src/Output/JsonResponse.php` — encoding JSON.
- `tests/01.02-response/index.php` dan `tests/01.02-response/views/home.php` —
  semua contoh di halaman ini.

## Status verifikasi

Diuji 28 Oktober 2026 dengan PHP 8.4.14 lewat
`http://localhost:8089/01.02-response/`. Aplikasi uji memuat `vendor/`
workspace, jadi yang diuji adalah `src/` repo ini, bukan paket terbit.
Halaman [instalasi](01-instalasi.md) tetap diuji dengan paket terbit karena
halaman itu menjelaskan alur instalasi user.

| Endpoint | Status | Body | `Content-Type` |
|---|---|---|---|
| `/` | `200` | `Halo, Selvi Framework!` | `text/html` |
| `/json` | `200` | JSON rapi | `text/html` |
| `/json-header` | `200` | JSON rapi | `application/json` |
| `/halaman` | `200` | HTML utuh dengan `judul` dan `nama` | `text/html` |
| `/halaman-hilang` | `500` | kosong (template tidak ada) | — |
| `/tidak-ditemukan` | `404` | `Data tidak ditemukan` | `text/html` |
| `/tanpa-isi` | `204` | kosong | — |
| `/tanpa-return` | `500` | teks hasil `echo` | `text/html` |

Endpoint `/tanpa-return` dan `/halaman-hilang` sengaja dibuat untuk
membuktikan baris pada tabel kesalahan umum: `echo` tanpa `return` berakhir
`500`, dan nama template yang salah juga `500`.
