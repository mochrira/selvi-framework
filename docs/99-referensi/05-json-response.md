# Referensi: `Selvi\Output\JsonResponse`

Respons JSON. Turunan `Response`, dibuat lewat helper `jsonResponse()` (lihat
bagian Helper). Tidak ada method statis.

## Method instance

### `getData()`

**Kembalian:** `?array` — data sebelum di-encode.

### `getOptions()`

**Kembalian:** `int` — flag `json_encode()` yang dipakai.

### `setData($data)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$data` | `array` | wajib | data baru; tidak menerima `null` |

**Kembalian:** `void`.

### `setOptions($options)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$options` | `int` | wajib | flag `json_encode()` baru |

**Kembalian:** `void`.

### `send()`

Mengisi body dari `json_encode($data, $options)` lalu memanggil `send()` milik
kelas induk (`Response`). Bila data `null`, body kosong — berguna untuk `204`.

**Kembalian:** `void` — proses berhenti.

## Helper

### `jsonResponse($data, $code, $options)`

Membuat instance `JsonResponse`.

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$data` | `?array` | `null` | data yang di-encode; `null` menghasilkan body kosong |
| `$code` | `int` | `200` | status HTTP |
| `$options` | `int` | `JSON_PRETTY_PRINT` | flag `json_encode()` |

**Kembalian:** `Selvi\Output\JsonResponse`.

Helper ini tidak mengatur header `Content-Type`; set manual bila diperlukan.

## Sumber

- `src/Output/JsonResponse.php` — implementasi.
- `src/Output/Response.php` — kelas induk (`send()`).
- `src/Helpers/ResponseHelper.php` — helper `jsonResponse()`.
- `tests/01.02-response/index.php` — aplikasi uji.

## Status verifikasi

Diuji 28 Oktober 2026 (PHP 8.4.14) lewat
`http://localhost:8089/01.02-response/`:

| Kasus | Hasil |
|---|---|
| `jsonResponse()` | `200`, JSON rapi |
| `jsonResponse()` dengan `JSON_UNESCAPED_SLASHES` | garis miring tidak di-escape |
| `jsonResponse(null, 204)` | `204`, body kosong |

Belum diuji: `setData()`, `setOptions()`, `getData()`, `getOptions()`.
Semuanya dijelaskan dari kode sumber.
