# Referensi: `Selvi\View`

Perender template PHP. Satu file template berisi halaman HTML utuh. Instance
dibuat lewat helper `view()` (lihat bagian Helper); konstruktor `View` dipakai
internal.

Aturan yang berlaku untuk semua method:

- Nama file harus lengkap dengan ekstensi: `home.php`, bukan `home`.
- File dicari berurutan pada properti statis `View::$paths`. Folder bawaan
  `src/views` selalu ada di urutan pertama, lalu folder dari `addPath()`.
- File yang tidak ditemukan menghasilkan
  `TypeError: Cannot assign null to property Selvi\View::$file` dan berakhir
  `500`.

## Method statis

### `addPath($path)`

Menambah folder pencarian template.

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$path` | `string` | wajib | path absolut folder template |

**Kembalian:** `void`. Path berlaku untuk proses berjalan.

## Method instance

### `setVar($name, $value)`

Menyimpan satu variabel untuk template.

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | wajib | nama variabel di template |
| `$value` | `mixed` | wajib | nilai variabel |

**Kembalian:** instance `View` (dapat dirangkai).

Catatan: penyimpanan bersifat statis (`View::$vars`), sehingga variabel tetap
ada untuk pemanggilan view berikutnya dalam proses yang sama.

### `render($code)`

Merender template dan mengubahnya menjadi respons.

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$code` | `int` | `200` | status HTTP hasil render |

**Kembalian:** `Selvi\Output\Response`.

Output template ditangkap dengan output buffering, lalu dibungkus sebagai
`Response`.

### `include()`

Mencetak template langsung ke output.

**Kembalian:** `void`.

Karena mengembalikan `void`, jangan dipakai sebagai nilai balik callback route;
gunakan `render()`.

## Helper

### `view($file, $vars)`

Membuat instance `View` dan menyimpan variabel untuk template.

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$file` | `string` | wajib | nama file template dengan ekstensi |
| `$vars` | `array` | `[]` | variabel yang diekstrak di template |

**Kembalian:** `Selvi\View`. Panggil `render()` untuk mendapatkan `Response`.

## Sumber

- `src/View.php` — pemuatan, variabel, `render()`, `include()`.
- `src/Helpers/LoadHelper.php` — helper `view()`.
- `src/views/default.php` — contoh halaman bawaan.
- `tests/01.02-response/views/home.php` dan
  `tests/01.02-response/index.php` — aplikasi uji.

## Status verifikasi

Diuji 28 Oktober 2026 (PHP 8.4.14) lewat
`http://localhost:8089/01.02-response/`:

| Kasus | Hasil |
|---|---|
| `addPath()` + `view()` + `render()` | `200`, HTML utuh |
| Variabel `judul` dan `nama` di template | tercetak benar |
| `view('tidak-ada')->render()` | `500` (`TypeError` pada `View::$file`) |

Belum diuji: `include()`, pemanggilan `setVar()` secara langsung, dan sifat
statis `View::$vars`. Ketiganya dijelaskan dari kode sumber. `setVar()` terpakai
secara tidak langsung oleh `view()`.
