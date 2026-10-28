# AGENTS.md

Panduan untuk agent AI mana pun yang mengembangkan Selvi Framework.
Baca berurutan: Bagian 1 adalah aturan umum, bagian berikutnya makin spesifik
ke source code framework ini.

---

## 1. Aturan dasar (berlaku untuk semua tugas)

1. **Baca dulu, ubah kemudian.** Sebelum mengubah kode, baca `README.md`,
   file yang akan diubah, dan file lain yang memakainya (`grep` nama kelas
   atau method tersebut di `src/` dan `tests/`).
2. **Perubahan kecil dan fokus.** Kerjakan hanya yang diminta. Jangan
   merapikan, memformat ulang, atau me-rename hal lain dalam file yang sama.
3. **Jangan merusak API publik.** Semua yang ada di `src/` dipakai user lewat
   Composer, jadi perubahan signature, nama method, atau perilaku harus
   diminta secara eksplisit. Untuk menambah kemampuan, tambahkan method/kelas
   baru daripada mengubah yang lama.
4. **Jangan tambah dependency** ke `composer.json` kecuali benar-benar perlu
   dan sudah disetujui. Framework ini hanya bergantung pada `symfony/console`
   dan harus tetap ringan.
5. **Jangan commit** `vendor/`, `site/`, `private/.ENV`, atau file sementara.
6. **Jangan membuat file rencana/notes** (mis. `PLAN.md`, `NOTES.md`) kecuali
   diminta. Hasil kerja berupa perubahan kode dan dokumentasi yang relevan.
7. **Kalau ragu atau ada dua pendekatan yang sama masuk akal, tanya user**
   sebelum menulis kode.

### Bahasa

- Nama kelas, method, variabel, dan komentar teknis: **Inggris** sesuai gaya
  kode yang ada.
- Pesan error yang dilihat user, docblock penjelas, README, dan jawaban ke
  user: **Indonesia**.

### Alur kerja standar

1. Pahami tujuan dan cari kode terkait.
2. Tentukan perubahan terkecil yang menyelesaikan masalah.
3. Terapkan perubahan.
4. Verifikasi (lihat Bagian 3).
5. Laporkan file yang berubah dan cara memverifikasinya.

---

## 2. Gambaran besar framework

Selvi adalah micro-framework PHP tanpa ORM berat dan tanpa container kompleks.
Semua state disimpan pada **registry statis**, dan alur request sangat pendek.

Persyaratan runtime: **PHP >= 8.1** (`composer.json`). Saat ini koneksi
database hanya tersedia untuk **MySQL/MariaDB**.

Peta direktori:

| Path | Tanggung jawab |
|------|----------------|
| `src/Framework.php` | Entry point `Framework::run()`: dispatch web atau CLI |
| `src/Router.php`, `src/Routing/` | Registry route, `Route`, `RouteGroup` |
| `src/Injector.php`, `src/Factory.php` | Resolusi dependency dan instantiasi kelas (singleton per kelas) |
| `src/Input/` | `Request` (method, header, post, get, raw, file) dan `Uri` (base URL, segmen) |
| `src/Output/` | `Response` dan `JsonResponse`; selalu diakhiri `send()` |
| `src/Helpers/` | Fungsi global: `response()`, `jsonResponse()`, `inject()`, `view()`, `parseSort()` |
| `src/Exception.php`, `src/Exception/` | Exception framework dan `Handler` (mapping exception → handler) |
| `src/Model.php`, `src/Model/`, `src/Data/` | Model berbasis PHP attribute + `DataObject` |
| `src/Database/` | `Manager` (koneksi), `DB` (facade), query builder, schema, migration, seeder |
| `src/View.php`, `src/views/` | Render template PHP sederhana |
| `src/Cli.php` | Registry nama perintah CLI |
| `tests/` | Aplikasi contoh sekaligus harness uji manual (bukan unit test) |
| `docs/` | Knowledge base Markdown: instalasi, konsep, dan referensi fitur |

### Alur request web

```
index.php → Route::get(...) → Selvi\Framework::run()
  → Request/Uri (Factory)
  → Router::resolve(method, uri)
  → Injector::resolve(callback, route params)
  → middleware (parameter + next)
  → callback → Response::send()
```

Konsekuensi penting:

- Callback route **wajib** mengembalikan objek `Selvi\Output\Response`
  (`response()`, `jsonResponse()`, `View::render()`), bukan `echo`.
- Callback boleh berupa closure, `[objek, 'method']`, atau string
  `'Namespace\Kelas@method'`.
- Parameter callback di-resolve berdasarkan **nama** dari route (`{idKontak}`)
  lebih dulu, lalu berdasarkan **tipe** lewat `Factory`. Jadi nama parameter
  harus sama dengan placeholder URI.
- `Framework::run()` langsung `die()` di dalam `send()`. Jangan menambah logika
  setelah pemanggilan itu.
- Jika `php_sapi_name() === 'cli'`, `run()` mengalihkan ke `Cli::listen()`.

---

## 3. Verifikasi perubahan

**Tidak ada unit test otomatis di repo ini** (tidak ada PHPUnit). Jangan
mengklaim "test lulus" tanpa bukti.

Urutan verifikasi yang dipakai:

1. **Cek sintaks** untuk setiap file yang diubah:
   `php -l path/to/file.php`
   Jika `php` tidak tersedia di environment, katakan demikian dan minta user
   menjalankannya.
2. **Cek autoload** hanya jika menambah/memindahkan kelas:
   `composer dump-autoload` (PSR-4: `Selvi\` → `src/`, `Selvi\Tests\` → `tests/app`).
3. **Uji manual lewat harness `tests/`** bila perubahan menyentuh routing,
   request, response, database, atau model. Selalu ikuti alur di
   `tests/README.md`. Ringkasnya:
   - **Cek container lebih dulu** (sering sudah berjalan):
     `docker ps --filter name=selvi_test_dev --format '{{.Names}} {{.Status}}'`
   - Hanya jika belum berjalan, jalankan
     `docker compose -f tests/.docker/compose.yaml up -d`
     (butuh network eksternal `environment` dan `database`).
   - Gunakan image `mochrira/dev` dari compose tersebut. Jangan memakai image
     lain dan jangan membuat container baru di luar compose.
   - Root repo di-mount ke `/workspace` dan Apache menyajikan
     `/workspace/tests`, sehingga folder baru di `tests/` langsung dapat diuji
     dari host di `http://localhost:8089/{folder}/` (port mengikuti `ports:`
     pada `.docker/compose.yaml`).
   - Uji HTTP dari host. `docker exec` hanya untuk perintah CLI (`composer`,
     `php -l`) karena PHP tidak tersedia di host.
   - Endpoint contoh ada di `tests/app/Config/routes.php`.
   - **Jangan** menjalankan `composer install`/`composer require` di folder uji
     selain `01.01-instalasi/`. Folder lain memuat `vendor/` workspace dengan
     `require __DIR__.'/../../vendor/autoload.php';` seperti `tests/index.php`,
     sehingga yang diuji adalah `src/` lokal.
   - `tests/01.01-instalasi/` adalah bukti uji halaman
     `docs/01-memulai/01-instalasi.md`. Jangan menghapusnya.
   - Migrasi: `php tests/console.php db:migrate main up` (nama perintah dari
     `#[AsCommand]` di `src/Database/Migration.php`; argumen: koneksi dan
     arah `up`/`down`, opsi `--step` dan `--all`).
4. **Bandingkan perilaku sebelum/sesudah** untuk endpoint yang terdampak.

Catatan pembagian peran:

- Perubahan `src/` diuji lewat harness `tests/index.php` (memuat `vendor/`
  repo, sehingga `Selvi\` mengarah ke `src/` lokal).
- Panduan `docs/` diuji lewat folder di `tests/` yang **namanya sama dengan
  nama file halaman**: `docs/01-memulai/01-instalasi.md` →
  `tests/01.01-instalasi/`.
- Pengecualian: `01.01-instalasi/` sengaja menginstal paket terbit dari
  Packagist karena halaman itu memang menguji alur instalasi user. Folder uji
  halaman lain memakai `vendor/` workspace dan menguji `src/` lokal.

Jika verifikasi tidak bisa dijalankan, sampaikan alasannya secara eksplisit.

---

## 4. Konvensi source code

- Satu kelas per file, path sesuai namespace (`Selvi\Routing\Route` →
  `src/Routing/Route.php`).
- Indentasi 4 spasi. Gaya penempatan kurung kurawal di repo ini campuran
  (sebagian `{` di baris baru, sebagian di baris yang sama): ikuti gaya file
  yang sedang diedit dan jangan memformat ulang file hanya demi keseragaman.
- `declare(strict_types=1);` wajib untuk file baru. Jangan menambahkannya
  massal ke file lama yang belum memilikinya.
- Interface diberi prefix `I` dan diletakkan di `Interfaces/`
  (`IQueryBuilder`, `IConnection`, `IGrammar`, ...).
- Gunakan `#[\Override]` saat mengimplementasikan method interface.
- Type hint dan return type sedetail mungkin; pakai union type PHP 8.1
  (`string | null`, `Closure | array | string`).
- Jangan menulis `echo`, `die`, atau `http_response_code()` di luar
  `src/Output/`. Semua output lewat `Response::send()`.
- Exception framework: gunakan `Selvi\Exception` (dengan `codeString`) atau
  `HttpException`/`DatabaseException`, bukan `\Exception` polos, agar handler
  menghasilkan JSON yang konsisten.
- Pesan exception harus menjelaskan penyebab dan tindakan, contoh gaya yang
  sudah ada: `"Koneksi '{$name}' tidak terdaftar."`,
  `"Operasi UPDATE memerlukan setidaknya satu kondisi WHERE ..."`.

---

## 5. Detail komponen (bila mengubah bagian ini)

### Routing — `src/Routing/Route.php`, `src/Router.php`

- `Route::get/post/patch/delete/options` menerima `callable | string | array`.
- `Route::group()` dan `withMiddleware()` mengisi `RouteGroup`; middleware
  dijalankan berurutan dan menerima `next` sebagai parameter.
- Pattern URI memakai `{nama}` dan dicocokkan dengan regex. Perubahan pada
  `match()`/`compile()` berdampak ke semua route, jadi uji semua endpoint di
  `tests/app/Config/routes.php`.
- `Router` adalah array statis; route harus didaftarkan sebelum
  `Framework::run()`.

### Dependency injection — `src/Injector.php`, `src/Factory.php`

- `Factory` menyimpan instance per nama kelas (singleton) di
  `Factory::$instances`. Jangan mengubah perilaku caching tanpa alasan jelas.
- `Injector::getDependencies()` memakai nama parameter dulu, lalu tipe.
  Parameter **tanpa type hint** akan gagal karena `getType()` bernilai `null`.
  Parameter builtin tanpa default menjadi `null`, dan parameter class akan
  di-resolve lewat `Factory`. Jadi setiap parameter baru pada
  callback/middleware harus punya type hint atau default value.

### Database — `src/Database/`

- `Manager::add(name, config)` mendaftarkan koneksi; nama yang sudah ada
  **tidak** ditimpa. `DB::table()`, `DB::schema()`, `DB::connection()` adalah
  facade pembuat builder.
- Driver terdaftar hanya `mysql`; `sqlsrv` baru punya `SQLSrvResult`.
  Menambah driver berarti membuat `IConnection` + `IGrammar` baru dan
  mendaftarkannya di `Manager::$drivers`.
- `UPDATE`/`DELETE` via builder menolak berjalan tanpa `WHERE`
  (`LogicException`). Pertahankan pengaman ini.
- Grammar MySQL membangun SQL di `Drivers/MySQL/MySQLGrammar.php`; sanitasi
  identifier di `MySQLSanitizer.php`. Jangan menyusun SQL dengan string
  concatenation di luar grammar.
- Migration: file `YYYYMMDD_NN_konteks.php` mengembalikan
  `function (Schema $schema, string $direction)` dengan blok `up`/`down`.
  Perintah CLI didaftarkan lewat attribute `#[AsCommand('db:migrate')]`.

### Model — `src/Model.php`, `src/Model/Attributes/`

- Metadata dibaca lewat reflection dan di-cache di `attributes_cache` /
  `properties_cache`. Jika mengubah cara attribute dibaca, invalidasi cache
  ikut dipertimbangkan.
- `#[Table(name, schema)]` wajib ada; `#[Column(name, key, cast, serialize)]`
  memetakan properti ke kolom; `#[BelongsTo]` dan `#[Aggregate]` untuk relasi.
- `Model::create()` mengisi objek dari array input dan memakai `lastId()`.

### Response dan View

- `response($content, $code)` → `Response`; `jsonResponse($data, $code, $options)`
  → `JsonResponse` (default `JSON_PRETTY_PRINT`).
- `View::render()` mengembalikan `Response`; `View::include()` langsung
  mencetak. Di controller selalu pakai `render()`.
- `View::$vars` bersifat statis (dibagi antar view) — perhatikan kebocoran
  variabel bila menambah fitur view.

### Exception handling — `src/Exception/Handler.php`

- Handler dipilih berdasarkan tipe exception pertama yang cocok, fallback ke
  key `default`. `Handler::setDefaultHandlers()` mendaftarkan handler JSON
  untuk `Selvi\Exception`, `DatabaseException`, `HttpException`, dan
  `Throwable`.

### CLI — `src/Cli.php`

- `Cli::register($name, $class)` lalu `Cli::listen()` membaca `$argv`.
  Aplikasi bisa juga memakai Symfony Console langsung (lihat
  `tests/console.php`).

---

## 6. Dokumentasi

- Dokumentasi ada di `docs/`, berupa Markdown biasa tanpa generator situs.
  `mkdocs.yml` sudah dihapus; jangan menambahkannya kembali.
- `docs/README.md` adalah peta dokumentasi dan daftar status penulisan.
- Setiap halaman memakai urutan bagian tetap: Tujuan, Prasyarat, Contoh,
  Output yang diharapkan, Referensi API, Kesalahan umum, Sumber, dan Status
  verifikasi.
- Bagian **Sumber** wajib menunjuk file nyata di repo. Bagian **Status
  verifikasi** wajib jujur: tandai `BELUM DIVERIFIKASI` bila perilakunya hanya
  diturunkan dari membaca kode.
- Jangan membuat link ke halaman yang belum ada. Perbarui
  `docs/README.md` saat menambah atau menyelesaikan halaman.
- `README.md` di root adalah ringkasan cepat. Jika perilaku yang terlihat user
  berubah, perbarui README dan halaman `docs/` yang terkait.

---

## 7. Checklist sebelum menyatakan selesai

- [ ] Perubahan hanya menyentuh hal yang diminta.
- [ ] Tidak ada perubahan API publik yang tidak diminta.
- [ ] `php -l` bersih untuk semua file yang diubah (atau alasannya dilaporkan).
- [ ] Endpoint terkait di `tests/app/Config/routes.php` masih berjalan.
- [ ] README diperbarui bila perilaku yang terlihat user berubah.
- [ ] Ringkasan akhir menyebut file yang berubah dan cara verifikasinya.
