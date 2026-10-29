# Referensi: `Selvi\Output\Response`

Respons teks. Instance dibuat lewat helper `response()` (lihat bagian Helper).
Tidak ada method statis. Untuk JSON, lihat
[`JsonResponse`](05-json-response.md).

## Method instance

### `getContent()`

**Kembalian:** `?string` — isi body saat ini.

### `getCode()`

**Kembalian:** `int` — status HTTP saat ini.

### `setContent($content)`

Mengganti isi body setelah objek dibuat.

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$content` | `?string` | wajib | isi body baru |

**Kembalian:** `void`.

### `setCode($code)`

Mengganti status HTTP setelah objek dibuat.

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$code` | `int` | wajib | status HTTP baru |

**Kembalian:** `void`.

### `cookie($name, $value, $expire, $path, $domain, $secure, $httponly)`

Mengirim cookie saat itu juga lewat `setcookie()`.

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | wajib | nama cookie |
| `$value` | `string` | `''` | nilai cookie |
| `$expire` | `int` | `0` | waktu kedaluwarsa berupa Unix timestamp; `0` = sesi |
| `$path` | `string` | `''` | path berlaku |
| `$domain` | `string` | `''` | domain berlaku |
| `$secure` | `bool` | `false` | hanya kirim lewat HTTPS |
| `$httponly` | `bool` | `false` | tidak dapat dibaca JavaScript |

**Kembalian:** `void`.

### `send()`

Mengirim respons dan mengakhiri proses. Dipanggil framework; tidak perlu
dipanggil sendiri. Urutannya: `http_response_code($code)` (dilewati saat CLI),
`echo` isi body, lalu `die()`.

**Kembalian:** `void` — tetapi proses berhenti.

## Helper

### `response($content, $code)`

Membuat instance `Response`.

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$content` | `?string` | `''` | isi body |
| `$code` | `int` | `200` | status HTTP |

**Kembalian:** `Selvi\Output\Response`.

Helper ini tidak mengatur header apa pun, termasuk `Content-Type`.

## Sumber

- `src/Output/Response.php` — implementasi.
- `src/Helpers/ResponseHelper.php` — helper `response()`.
- `tests/01.02-response/index.php` — aplikasi uji.

## Status verifikasi

Diuji 28 Oktober 2026 (PHP 8.4.14) lewat
`http://localhost:8089/01.02-response/`:

| Kasus | Hasil |
|---|---|
| `response()` dengan teks | `200`, body teks |
| `response()` dengan status `404` | `404` |
| `setContent()` + `setCode(201)` | body berubah, status `201` |
| `cookie('demo', 'abc123')` | header `Set-Cookie: demo=abc123` |
| `send()` lewat callback | status dan body sesuai |

Belum diuji: `getContent()` dan `getCode()`. Keduanya dijelaskan dari kode
sumber.
