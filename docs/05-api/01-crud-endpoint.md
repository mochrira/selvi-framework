---
id: api-crud-endpoint
title: Panduan Endpoint CRUD API
type: panduan
tags: [api, crud, migration, model, controller, validasi, best-practice, openapi, swagger-ui]
prerequisites: [database-migrasi, model, controller, request, response]
verified: 2026-11-05
applies_to: Selvi 3.x
---

# Panduan Endpoint CRUD API

Halaman ini menuntun pembuatan satu resource API dari nol: migrasi tabel,
spesifikasi OpenAPI, model, route, controller, sampai pengujian dengan `curl`.
Contoh yang dipakai adalah resource `produk` dengan operasi list, detail,
create, update, dan delete.

Urutan yang dipakai:

```text
migrasi tabel → jalankan migrasi → tulis spesifikasi → Swagger UI
→ setujui spesifikasi → model → route → controller → uji endpoint
```

Model, route, dan controller tidak dibuat sebelum spesifikasi disetujui. Ganti
nama `produk`, nama kolom, dan aturan validasi sesuai domain aplikasi.

## Tujuan

Menghasilkan endpoint CRUD yang:

- struktur tabelnya dibuat dan dibatalkan lewat migrasi;
- membaca/menulis data lewat model, bukan SQL tersebar di controller;
- memvalidasi input di batas API dan mengembalikan status HTTP yang benar;
- mengembalikan bentuk JSON yang konsisten untuk sukses maupun gagal.

## Jawaban singkat

```php
Route::get('/produk', 'App\Controllers\ProdukController@result');
Route::get('/produk/{id}', 'App\Controllers\ProdukController@row');
Route::post('/produk', 'App\Controllers\ProdukController@insert');
Route::patch('/produk/{id}', 'App\Controllers\ProdukController@update');
Route::delete('/produk/{id}', 'App\Controllers\ProdukController@delete');
```

Urutan pembuatannya: migrasi tabel → spesifikasi OpenAPI → model → route →
controller → uji `curl`. Controller hanya mengurus HTTP; query ada di model.

## Prasyarat

- [Migrasi](../04-database/05-migrasi.md), [Model](../03-model/01-model.md),
  [Controller](../01-memulai/04-controller.md),
  [Request](../01-memulai/03-request.md), dan
  [Response](../01-memulai/02-response.md) sudah dipahami.
- Koneksi `main` sudah terdaftar seperti pada
  [Koneksi database](../04-database/01-koneksi.md).
- Namespace `App\` dipetakan ke folder `app/` pada `autoload.psr-4`
  `composer.json`, lalu jalankan `composer dump-autoload`.
- Ekstensi `json` aktif (bawaan PHP) dan `symfony/console` tersedia untuk
  perintah migrasi.

## Rancangan kontrak API

Tentukan kontrak setelah struktur tabel disepakati, sebelum menulis kode
aplikasi. Untuk resource `produk`:

| Method | URI | Fungsi | Sukses | Gagal |
|---|---|---|---|---|
| `GET` | `/produk` | daftar produk | `200` | `500` |
| `GET` | `/produk/{id}` | detail produk | `200` | `400`, `404` |
| `POST` | `/produk` | buat produk | `201` | `400`, `422` |
| `PATCH` | `/produk/{id}` | ubah sebagian field | `200` | `400`, `404`, `422` |
| `DELETE` | `/produk/{id}` | hapus produk | `204` | `400`, `404` |

Tabel ini ringkasan. Kontrak lengkap beserta schema request/response ditulis
sebagai OpenAPI pada langkah 4.

Keputusan yang dipakai di halaman ini:

- URL memakai kata benda jamak (`/produk`), bukan kata kerja.
- Update memakai `PATCH` karena hanya field yang dikirim yang diubah. Framework
  menyediakan `Route::get()`, `post()`, `patch()`, `delete()`, dan `options()`;
  tidak ada `Route::put()`.
- Body request dan body sukses memakai JSON objek.
- Error framework memakai bentuk `{ "code": "...", "message": "..." }`.
  Error validasi menambahkan `data.errors`.
- Nama field JSON mengikuti nama properti model (`idProduk`, `createdAt`),
  sedangkan kunci database tetap `snake_case` (`id_produk`, `created_at`).

## 1. Siapkan bootstrap dan konfigurasi

Pisahkan bootstrap dari konfigurasi aplikasi:

- `bootstrap.php` — autoload, `.ENV`, handler exception, lalu memuat config.
- `Config/database.php` — pendaftaran koneksi.
- `Config/routes.php` — daftar endpoint.

`bootstrap.php`:

```php
<?php

declare(strict_types=1);

use Selvi\Env;
use Selvi\Exception\Handler;

require __DIR__.'/vendor/autoload.php';

Env::load(__DIR__.'/.ENV');

Handler::setDefaultHandlers();
Handler::listen();

require __DIR__.'/Config/database.php';
```

`Config/database.php`:

```php
<?php

use Selvi\Database\Manager;
use Selvi\Env;

Manager::add('main', [
    'driver' => Env::get('DB_DRIVER', 'mysql'),
    'host' => Env::get('DB_HOST'),
    'port' => Env::get('DB_PORT'),
    'username' => Env::get('DB_USER'),
    'password' => Env::get('DB_PASS'),
    'database' => Env::get('DB_NAME'),
]);
```

`console.php` memakai bootstrap yang sama, lalu menambahkan path migrasi dan
perintah Console:

```php
<?php

require __DIR__.'/bootstrap.php';

use Selvi\Database\Migration;
use Symfony\Component\Console\Application;

Migration::add('main', __DIR__.'/app/Migrations');

$app = new Application('Selvi App', '1.0.0');
$app->addCommand(new Migration());
$app->run();
```

Praktik yang perlu dijaga:

- Kredensial tetap di `.ENV`; jangan ditulis di `Config/database.php`.
- `Handler::setDefaultHandlers()` dan `Handler::listen()` dipasang sekali di
  bootstrap agar exception dari controller menjadi respons JSON, bukan halaman
  error PHP.
- `Route::...` tetap ditulis di `Config/routes.php`; bootstrap tidak memuatnya.
  `index.php` yang memuat route, sehingga `console.php` bisa menjalankan
  migrasi sebelum file route dibuat.
- `index.php` dan `console.php` memakai bootstrap yang sama, sehingga
  konfigurasi koneksi tidak ditulis dua kali.

## 2. Buat migrasi tabel

Buat `app/Migrations/20261105_01_create_produk.php`:

```php
<?php

use Selvi\Database\Builder\DDL\Blueprint;
use Selvi\Database\Schema;

return function (Schema $schema, string $direction) {

    if($direction === 'up') {
        $schema->create('produk', function (Blueprint $table) {
            $table->integer('id_produk')->key()->autoIncrement();
            $table->string('nama', 100);
            $table->integer('harga');
            $table->integer('stok')->default(0);
            $table->datetime('created_at');
            $table->datetime('updated_at');
        });
    }

    if($direction === 'down') {
        $schema->drop('produk');
    }

};
```

Alasan pemilihan tipe:

- `id_produk` menjadi satu-satunya kolom `key: true`; kolom ini dipakai
  `find()`, `update()`, dan `delete()` pada model.
- `harga` disimpan sebagai integer rupiah, bukan `float`, agar tidak ada
  pembulatan.
- `stok` diberi default `0` supaya insert yang tidak mengirim stok tetap valid.
- `created_at`/`updated_at` dibuat `NOT NULL` dan diisi controller. Framework
  tidak mengisi timestamp otomatis.
- `up` dan `down` harus simetris. Migrasi yang tidak bisa dibatalkan menyulitkan
  rollback dan pengujian.

## 3. Jalankan migrasi

```sh
php console.php db:migrate main up
```

Output yang diharapkan:

```text
[success:up] 20261105_01_create_produk.php berhasil dijalankan
```

Perintah kedua akan melewati file yang sama:

```text
[skipped:up] 20261105_01_create_produk.php berhasil dijalankan pada 2026-11-05 02:20:12
```

Untuk membatalkan migrasi terakhir saat pengembangan:

```sh
php console.php db:migrate main down
```

Jangan mengubah file migrasi yang sudah dijalankan di lingkungan bersama.
Buat file baru untuk perubahan berikutnya.

## 4. Tulis spesifikasi OpenAPI

Struktur tabel sudah disepakati lewat migrasi. Sekarang tulis spesifikasi
sebagai kontrak API sebelum model, route, dan controller dibuat.
Pisahkan file utama, schema, dan operasi path:

```text
specs/
├── index.yaml
├── paths/
│   ├── produk.yaml
│   └── produk-single.yaml
└── schemas/
    └── Produk.yaml
```

`specs/index.yaml` menjadi pintu masuk. File ini memuat `info`, `servers`, lalu
menunjuk file path dan schema lewat `$ref` relatif.

```yaml
openapi: 3.0.3

info:
  title: Produk API
  version: 1.0.0
  license:
    name: MIT
    url: https://opensource.org/license/mit
  description: |
    Spesifikasi CRUD resource `produk` untuk aplikasi contoh Selvi Framework.
    Kontrak ini menjadi acuan sebelum migrasi, model, controller, dan route
    dibuat.

security: []

servers:
  - url: /05.01-crud-endpoint
    description: Aplikasi uji lokal

tags:
  - name: Produk
    description: Operasi CRUD resource produk

paths:
  /produk:
    $ref: './paths/produk.yaml'
  /produk/{id}:
    $ref: './paths/produk-single.yaml'
```

Aturan yang dipakai:

- `$ref` di `index.yaml` relatif terhadap folder `specs/`.
- File di `specs/paths/` merujuk schema dengan `../schemas/...`.
- Example hanya ditulis di file schema, bukan diulang di setiap endpoint.
- Schema request ditulis inline di `requestBody` operasi yang membutuhkannya;
  tidak ada schema input terpisah.
- Response daftar ditulis inline di `paths/produk.yaml`; tidak ada schema
  pembungkus terpisah.
- Respons error (`400`, `404`, `422`) sengaja tidak didokumentasikan. Spesifikasi
  hanya memuat respons sukses.
- `security: []` menandakan contoh belum memakai autentikasi. Saat endpoint
  dilindungi middleware, ganti dengan skema keamanan yang sesuai.
- `count` pada response daftar adalah total baris yang cocok, bukan jumlah baris
  halaman.
- `createdAt`/`updatedAt` bertipe `string`, bukan `format: date-time`, karena
  model menyerialisasikannya sebagai `Y-m-d H:i:s` (bukan RFC 3339).

### Schema

Hanya ada satu schema produk. Bentuk response daftar ditulis langsung pada
operasi GET di file path.

#### `Produk.yaml`

```yaml
type: object
required: [idProduk, nama, harga, stok, createdAt, updatedAt]
properties:
  idProduk:
    type: integer
    example: 1
  nama:
    type: string
    example: Kopi Gayo
  harga:
    type: integer
    minimum: 0
    example: 85000
  stok:
    type: integer
    minimum: 0
    example: 12
  createdAt:
    type: string
    description: Format `Y-m-d H:i:s`.
    example: '2026-11-05 02:36:05'
  updatedAt:
    type: string
    description: Format `Y-m-d H:i:s`.
    example: '2026-11-05 02:36:05'
example:
  idProduk: 1
  nama: Kopi Gayo
  harga: 85000
  stok: 12
  createdAt: '2026-11-05 02:36:05'
  updatedAt: '2026-11-05 02:36:05'
```

### Path dan operasi

Satu file path berisi operasi HTTP untuk path tersebut. Schema input ditulis
inline pada `requestBody`, sehingga tidak ada file schema request tambahan.

#### `produk.yaml`

```yaml
get:
  tags: [Produk]
  operationId: resultProduk
  summary: Daftar produk
  description: Mengembalikan daftar produk dengan filter nama dan pagination.
  parameters:
    - name: q
      in: query
      description: Filter substring pada nama produk.
      schema:
        type: string
    - name: limit
      in: query
      description: Jumlah baris per halaman. Nilai di luar 1–100 dipaksa masuk rentang.
      schema:
        type: integer
        minimum: 1
        maximum: 100
        default: 30
    - name: offset
      in: query
      description: Jumlah baris yang dilewati. Nilai negatif dipaksa menjadi 0.
      schema:
        type: integer
        minimum: 0
        default: 0
  responses:
    '200':
      description: Daftar produk
      content:
        application/json:
          schema:
            type: object
            properties:
              data:
                type: array
                items:
                  $ref: '../schemas/Produk.yaml'
              count:
                type: integer
                description: Total baris yang cocok dengan filter, bukan jumlah baris halaman.
                example: 1

post:
  tags: [Produk]
  operationId: insertProduk
  summary: Buat produk
  description: Membuat satu produk baru.
  requestBody:
    required: true
    content:
      application/json:
        schema:
          type: object
          required: [nama, harga]
          properties:
            nama:
              type: string
              minLength: 1
              maxLength: 100
            harga:
              type: integer
              minimum: 0
            stok:
              type: integer
              minimum: 0
              default: 0
  responses:
    '201':
      description: Produk berhasil dibuat
      content:
        application/json:
          schema:
            $ref: '../schemas/Produk.yaml'
```

#### `produk-single.yaml`

```yaml
parameters:
  - name: id
    in: path
    required: true
    description: ID produk berupa angka.
    schema:
      type: integer
      minimum: 1

get:
  tags: [Produk]
  operationId: rowProduk
  summary: Detail produk
  responses:
    '200':
      description: Detail produk
      content:
        application/json:
          schema:
            $ref: '../schemas/Produk.yaml'

patch:
  tags: [Produk]
  operationId: updateProduk
  summary: Ubah sebagian field produk
  description: Hanya field yang dikirim yang diubah; `updatedAt` selalu diperbarui.
  requestBody:
    required: true
    content:
      application/json:
        schema:
          type: object
          minProperties: 1
          properties:
            nama:
              type: string
              minLength: 1
              maxLength: 100
            harga:
              type: integer
              minimum: 0
            stok:
              type: integer
              minimum: 0
  responses:
    '200':
      description: Produk setelah diubah
      content:
        application/json:
          schema:
            $ref: '../schemas/Produk.yaml'

delete:
  tags: [Produk]
  operationId: deleteProduk
  summary: Hapus produk
  responses:
    '204':
      description: Produk dihapus tanpa body
```

## 5. Sajikan dokumentasi dengan Swagger UI

Buat `specs/index.html`:

```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Produk API — Swagger UI</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.34.1/swagger-ui.css">
    <style>
        body { margin: 0; background: #fafafa; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>

    <script src="https://unpkg.com/swagger-ui-dist@5.34.1/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.34.1/swagger-ui-standalone-preset.js"></script>
    <script>
        window.ui = SwaggerUIBundle({
            url: './index.yaml',
            dom_id: '#swagger-ui',
            deepLinking: true,
            validatorUrl: null,
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIStandalonePreset
            ],
            layout: 'StandaloneLayout'
        });
    </script>
</body>
</html>
```

Buka `http://127.0.0.1:8089/05.01-crud-endpoint/specs/`. Server menyajikan
`index.html` sebagai halaman utama folder, lalu halaman memuat `./index.yaml`
dan menampilkan lima operasi serta schema `Produk`.

- Aset Swagger UI dimuat dari unpkg versi `5.34.1`. Jika lingkungan tidak punya
  akses internet, unduh `swagger-ui.css`, `swagger-ui-bundle.js`, dan
  `swagger-ui-standalone-preset.js` ke folder `specs/`, lalu ubah `href`/`src`
  menjadi path lokal.
- `url: './index.yaml'` relatif terhadap `specs/index.html`.
- `validatorUrl: null` mematikan validator online. Tanpa ini, Swagger UI
  menampilkan badge **INVALID** karena `validator.swagger.io` tidak bisa membaca
  host lokal seperti `selvi.local`.
- Tombol **Try it out** mengirim request ke `servers.url` pada spesifikasi.
- Pastikan seluruh file di `specs/` tidak ikut di-rewrite ke front controller.
  Kondisi `!-f` dan `!-d` seperti pada `.htaccess` aplikasi uji sudah cukup.
- Jika server mengirim YAML sebagai `application/octet-stream`, tambahkan
  `AddType application/yaml .yaml` pada `.htaccess`.

## 6. Review dan setujui spesifikasi

Model, route, dan controller belum boleh dibuat sebelum spesifikasi disetujui.
Review kontrak bersama pemilik domain atau reviewer:

- Path dan method HTTP sesuai kebutuhan operasi.
- Field wajib, tipe, rentang nilai, dan default sudah benar.
- Contoh response di schema mewakili data nyata.
- Nama field JSON konsisten dengan `toArray()` model.
- Respons error tidak didokumentasikan, sesuai kebijakan spesifikasi ini.
- `servers.url` sudah menunjuk lingkungan yang benar.

Setelah disetujui, spesifikasi menjadi acuan implementasi. Jika kebutuhan
berubah, ubah dan review spesifikasi lebih dulu, baru sesuaikan model, route,
dan controller.

## 7. Buat model

Buat `app/Models/Produk.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use DateTime;
use Selvi\Model;
use Selvi\Model\Attributes\Column;
use Selvi\Model\Attributes\Table;

#[Table('produk', schema: 'main')]
class Produk extends Model {

    #[Column('id_produk', key: true)]
    public int $idProduk;

    #[Column('nama')]
    public string $nama;

    #[Column('harga')]
    public int $harga;

    #[Column('stok')]
    public int $stok;

    #[Column('created_at')]
    public DateTime $createdAt;

    #[Column('updated_at')]
    public DateTime $updatedAt;

}
```

- `schema` pada `#[Table]` adalah **nama koneksi** (`main`), bukan nama
  database.
- Nama properti bebas, tetapi kunci JSON `toArray()` memakai nama properti.
  Karena itu API memakai `idProduk`, `createdAt`, dan `updatedAt`.
- Tipe properti harus cocok dengan nullability kolom. Semua kolom di migrasi
  `NOT NULL`, jadi properti tidak memakai `?`.
- `create()` menerima kunci **nama kolom** (`id_produk`), bukan nama properti.

## 8. Daftarkan route

Buat `app/Config/routes.php`:

```php
<?php

use Selvi\Routing\Route;

Route::get('/produk', 'App\Controllers\ProdukController@result');
Route::get('/produk/{id}', 'App\Controllers\ProdukController@row');
Route::post('/produk', 'App\Controllers\ProdukController@insert');
Route::patch('/produk/{id}', 'App\Controllers\ProdukController@update');
Route::delete('/produk/{id}', 'App\Controllers\ProdukController@delete');
```

`index.php`:

```php
<?php

require __DIR__.'/bootstrap.php';
require __DIR__.'/app/Config/routes.php';

\Selvi\Framework::run();
```

- `bootstrap.php` hanya memuat konfigurasi database. Route dimuat oleh
  `index.php` agar `console.php` tetap bisa dipakai sebelum file route
  dibuat.
- Pada aplikasi nyata, `App\Models\Produk` dan `App\Controllers\ProdukController`
  dimuat oleh autoload PSR-4. Folder uji memuat keduanya manual karena autoload
  repo hanya memetakan `Selvi\` dan `Selvi\Tests\`.
- Placeholder route dan nama parameter method harus sama: `{id}` dengan
  `string $id`.
- Nilai route selalu berupa string. Karena itu controller memvalidasi
  `ctype_digit($id)` sebelum mengubahnya menjadi `int`.
- Route didaftarkan sebelum `Framework::run()`.

## 9. Buat controller

Buat `app/Controllers/ProdukController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Produk;
use DateTime;
use Selvi\Exception;
use Selvi\Input\Request;
use stdClass;

class ProdukController {

    public function __construct(private Request $request) { }

    public function result() {
        $query = Produk::query();
        $q = trim((string) ($this->request->get('q') ?? ''));
        if($q !== '') $query->where([['nama', 'LIKE', '%'.$q.'%']]);

        $count = (clone $query)->count();

        $limit = (int) ($this->request->get('limit') ?? 30);
        $limit = min(100, max(1, $limit));
        $offset = max(0, (int) ($this->request->get('offset') ?? 0));
        $rows = $query->orderBy('id_produk', 'DESC')
            ->limit($limit)
            ->offset($offset)
            ->all();

        return jsonResponse([
            'data' => $rows->toArray(),
            'count' => $count
        ]);
    }

    public function row(string $id) {
        return jsonResponse($this->find($id)->toArray());
    }

    public function insert() {
        $input = $this->validate($this->jsonBody());
        $now = new DateTime();

        $produk = Produk::create([
            'nama' => $input['nama'],
            'harga' => $input['harga'],
            'stok' => $input['stok'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return jsonResponse($produk->toArray(), 201);
    }

    public function update(string $id) {
        $produk = $this->find($id);
        $input = $this->validate($this->jsonBody(), partial: true);

        foreach($input as $name => $value) {
            $produk->{$name} = $value;
        }
        $produk->updatedAt = new DateTime();
        $produk->update();

        return jsonResponse($produk->toArray());
    }

    public function delete(string $id) {
        $this->find($id)->delete();
        return jsonResponse(null, 204);
    }

    private function find(string $id): Produk {
        if(!ctype_digit($id)) {
            throw new Exception('ID produk harus berupa angka.', 'produk/invalid-id', 400);
        }

        $produk = Produk::find((int) $id);
        if($produk === null) {
            throw new Exception('Produk tidak ditemukan.', 'produk/not-found', 404);
        }

        return $produk;
    }

    /** @return array<string, mixed> */
    private function jsonBody(): array {
        $raw = $this->request->raw();
        if($raw === null || trim($raw) === '') {
            throw new Exception('Body JSON wajib diisi.', 'produk/invalid-json', 400);
        }

        $decoded = json_decode($raw);
        $data = json_decode($raw, true);
        if(!($decoded instanceof stdClass) || !is_array($data)) {
            throw new Exception('Body harus berupa objek JSON yang valid.', 'produk/invalid-json', 400);
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return array{nama?: string, harga?: int, stok?: int}
     */
    private function validate(array $data, bool $partial = false): array {
        $errors = [];
        $hasil = [];

        if(!$partial || array_key_exists('nama', $data)) {
            $nama = is_string($data['nama'] ?? null) ? trim($data['nama']) : '';
            if($nama === '') {
                $errors['nama'] = 'Nama wajib diisi.';
            } elseif(strlen($nama) > 100) {
                $errors['nama'] = 'Nama maksimal 100 karakter.';
            } else {
                $hasil['nama'] = $nama;
            }
        }

        if(!$partial || array_key_exists('harga', $data)) {
            $harga = $data['harga'] ?? null;
            if(!is_int($harga) || $harga < 0) {
                $errors['harga'] = 'Harga wajib berupa bilangan bulat >= 0.';
            } else {
                $hasil['harga'] = $harga;
            }
        }

        if(array_key_exists('stok', $data)) {
            $stok = $data['stok'];
            if(!is_int($stok) || $stok < 0) {
                $errors['stok'] = 'Stok wajib berupa bilangan bulat >= 0.';
            } else {
                $hasil['stok'] = $stok;
            }
        } elseif(!$partial) {
            $hasil['stok'] = 0;
        }

        if($partial && $hasil === [] && $errors === []) {
            throw new Exception('Tidak ada field yang bisa diperbarui.', 'produk/invalid', 422);
        }

        if($errors !== []) {
            throw (new Exception('Data produk tidak valid.', 'produk/invalid', 422))
                ->with('errors', $errors);
        }

        return $hasil;
    }

}
```

Nama method memakai kosakata operasi data, bukan nama gaya resource:
`result()` untuk daftar, `row()` untuk satu baris, lalu `insert()`, `update()`,
dan `delete()`.

Bagian penting:

| Bagian | Alasan |
|---|---|
| `__construct(private Request $request)` | dependency disuntikkan berdasarkan type hint; tidak perlu registrasi |
| `result()` | `limit` default 30 dan dibatasi 1–100; `count` berisi total baris yang cocok |
| `(clone $query)->count()` | query yang sama dipakai untuk count dan data, tanpa saling mengubah |
| `row()`, `update()`, `delete()` | memakai `find()` terpusat untuk validasi ID dan `404` |
| `insert()` | mengembalikan `201`, bukan `200` |
| `update()` | hanya field yang dikirim yang diubah; `updatedAt` selalu diperbarui |
| `delete()` | mengembalikan `204` tanpa body |
| `jsonBody()` | menolak body kosong dan JSON yang bukan objek dengan `400` |
| `validate()` | mengumpulkan semua error field, lalu melempar `422` dengan `data.errors` |
| `$data['stok']` default `0` | `POST` tanpa stok tetap valid; `PATCH` tidak memaksa stok |

Catatan pengembangan:

- Untuk domain yang lebih besar, pindahkan `validate()` ke kelas validator atau
  service. Controller sebaiknya tetap tipis dan hanya mengurus HTTP.
- Jika satu operasi menulis lebih dari satu tabel, bungkus dengan transaksi
  database. CRUD satu baris di halaman ini tidak memerlukannya.
- Jangan memakai `echo`, `die`, atau `http_response_code()` di controller.
  Selalu kembalikan `response()` atau `jsonResponse()`.

## 10. Uji endpoint

Gunakan `curl` dari host. Respons dirapikan oleh `JSON_PRETTY_PRINT` bawaan
`jsonResponse()`.

### Membuat data

```sh
curl -X POST http://127.0.0.1:8089/05.01-crud-endpoint/produk \
  -H 'Content-Type: application/json' \
  -d '{"nama":"Kopi Gayo","harga":85000,"stok":12}'
```

```json
{
    "idProduk": 1,
    "nama": "Kopi Gayo",
    "harga": 85000,
    "stok": 12,
    "createdAt": "2026-11-05 02:18:09",
    "updatedAt": "2026-11-05 02:18:09"
}
```

Status: `201 Created`.

### Daftar dan filter

```sh
curl 'http://127.0.0.1:8089/05.01-crud-endpoint/produk?q=kopi&limit=10&offset=0'
```

```json
{
    "data": [
        {
            "idProduk": 1,
            "nama": "Kopi Gayo",
            "harga": 85000,
            "stok": 12,
            "createdAt": "2026-11-05 02:18:09",
            "updatedAt": "2026-11-05 02:18:09"
        }
    ],
    "count": 1
}
```

Status: `200 OK`. `count` adalah total baris yang cocok dengan filter, bukan
jumlah baris pada halaman ini.

### Detail

```sh
curl http://127.0.0.1:8089/05.01-crud-endpoint/produk/1
```

```json
{
    "idProduk": 1,
    "nama": "Kopi Gayo",
    "harga": 85000,
    "stok": 12,
    "createdAt": "2026-11-05 02:18:09",
    "updatedAt": "2026-11-05 02:18:09"
}
```

Status: `200 OK`.

### Update sebagian

```sh
curl -X PATCH http://127.0.0.1:8089/05.01-crud-endpoint/produk/1 \
  -H 'Content-Type: application/json' \
  -d '{"stok":20}'
```

```json
{
    "idProduk": 1,
    "nama": "Kopi Gayo",
    "harga": 85000,
    "stok": 20,
    "createdAt": "2026-11-05 02:18:09",
    "updatedAt": "2026-11-05 02:18:16"
}
```

Status: `200 OK`. Field `nama` dan `harga` tidak berubah karena tidak dikirim.

### Validasi gagal

```sh
curl -X PATCH http://127.0.0.1:8089/05.01-crud-endpoint/produk/1 \
  -H 'Content-Type: application/json' \
  -d '{"nama":"","harga":-5}'
```

```json
{
    "code": "produk/invalid",
    "message": "Data produk tidak valid.",
    "data": {
        "errors": {
            "nama": "Nama wajib diisi.",
            "harga": "Harga wajib berupa bilangan bulat >= 0."
        }
    }
}
```

Status: `422 Unprocessable Entity`.

### Data tidak ditemukan

```sh
curl http://127.0.0.1:8089/05.01-crud-endpoint/produk/999
```

```json
{
    "code": "produk/not-found",
    "message": "Produk tidak ditemukan."
}
```

Status: `404 Not Found`.

### Hapus

```sh
curl -i -X DELETE http://127.0.0.1:8089/05.01-crud-endpoint/produk/1
```

```text
HTTP/1.1 204 No Content
```

Body kosong. Memanggil `GET /produk/1` setelahnya menghasilkan `404`.

## Checklist best practice

- [ ] Migrasi `up` dan `down` simetris; perubahan berikutnya memakai file baru.
- [ ] Satu resource satu controller; controller hanya mengurus HTTP.
- [ ] Semua input divalidasi di batas API, termasuk tipe, rentang, dan panjang.
- [ ] Status code sesuai operasi: `200`, `201`, `204`, `400`, `404`, `422`.
- [ ] Error memakai bentuk yang konsisten; jangan mengembalikan HTML.
- [ ] Pagination selalu dibatasi; jangan mengizinkan `limit` tanpa batas.
- [ ] Field yang boleh diubah ditentukan eksplisit; jangan menyalin seluruh body
      ke model.
- [ ] Nama kolom di `create()`/`update()` adalah nama kolom database.
- [ ] `PATCH` hanya mengubah field yang dikirim; `updated_at` diperbarui manual.
- [ ] Di produksi, jangan biarkan `trace` atau detail SQL keluar ke klien.
  Handler bawaan menampilkan keduanya; daftarkan handler sendiri lewat
  `Handler::set()` sesuai kebutuhan lingkungan.

## Referensi API

- [`Selvi\Routing\Route`](../99-referensi/02-route.md) — pendaftaran route dan
  method HTTP.
- [`Selvi\Input\Request`](../99-referensi/03-request.md) — `get()` dan `raw()`.
- [`Selvi\Output\JsonResponse`](../99-referensi/05-json-response.md) —
  `jsonResponse()` dan status code.
- [`Selvi\Database\Migration`](../99-referensi/19-migration.md) — perintah
  `db:migrate`.
- [`Selvi\Database\Builder\DDL\Blueprint`](../99-referensi/11-blueprint.md) —
  tipe dan modifier kolom.
- [`Selvi\Model`](../99-referensi/21-model.md) — `find()`, `create()`,
  `update()`, `delete()`, `toArray()`.
- [`Selvi\Database\Builder\DML\ModelQuery`](../99-referensi/22-model-query.md)
  — `where()`, `count()`, `orderBy()`, `limit()`, `offset()`.

## Kesalahan umum

| Gejala | Penyebab | Perbaikan |
|---|---|---|
| `Call to a member function send() on null` | method controller tidak mengembalikan `Response` | tambahkan `return` pada `response()`/`jsonResponse()` |
| `Undefined array key "stok"` saat `create()` | ada kolom non-key yang tidak dikirim | kirim semua kolom non-key, atau beri default di migrasi |
| `Class "App\Controllers\ProdukController" does not exist` | namespace tidak cocok dengan PSR-4 | samakan namespace dan jalankan `composer dump-autoload` |
| `Route PATCH '/produk/1' not found` | route belum didaftarkan atau memakai `put()` | daftarkan `Route::patch()`; framework tidak punya `put()` |
| `Argument #1 ($id) must be of type string, null given` | nama parameter method berbeda dari placeholder | samakan `{id}` dengan `string $id` |
| Body JSON selalu kosong | memakai `$request->post()` | gunakan `$request->raw()` lalu `json_decode()` |
| `SQLSTATE[42S22]: Column not found` | nama kolom model/migrasi tidak cocok | samakan `#[Column]` dengan nama kolom tabel |
| `500` berisi `sql` atau `trace` | memakai handler bawaan di produksi | daftarkan handler produksi lewat `Handler::set()` |
| `404` untuk ID yang jelas ada | kolom `key: true` salah atau `schema` bukan nama koneksi | periksa `#[Column(..., key: true)]` dan `#[Table(..., schema: 'main')]` |

## Sumber

- `specs/index.yaml` — file utama OpenAPI 3.0.
- `specs/schemas/` — schema response produk.
- `specs/paths/` — operasi HTTP per path.
- `specs/index.html` — halaman Swagger UI yang memuat spesifikasi.
- `app/Migrations/20261105_01_create_produk.php` — contoh migrasi.
- `app/Models/Produk.php` — model resource.
- `app/Controllers/ProdukController.php` — controller CRUD dan validasi.
- `app/Config/database.php` — pendaftaran koneksi.
- `app/Config/routes.php` — pendaftaran endpoint.
- `bootstrap.php`, `index.php`, dan `console.php` — perakitan web dan
  CLI.
- `src/Routing/Route.php` — method HTTP yang tersedia.
- `src/Injector.php` dan `src/Factory.php` — penyuntikan `Request` dan
  pembuatan controller.
- `src/Model.php` dan `src/Database/Builder/DML/ModelQuery.php` — operasi model.
- `src/Exception.php` dan `src/Exception/Handler.php` — bentuk error JSON.
- `tests/05.01-crud-endpoint/` — aplikasi uji halaman ini.

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 5 November 2026 dengan container `mochrira/dev` (PHP 8.4.14) dan
MariaDB 10.7.8. Aplikasi uji ada di `tests/05.01-crud-endpoint/` dan memuat
`vendor/` workspace, jadi yang diuji adalah `src/` repo ini. Folder uji memuat
kelas `App\` secara manual karena autoload repo hanya memetakan `Selvi\` dan
`Selvi\Tests\`; aplikasi nyata memakai PSR-4 seperti pada Prasyarat.

| Langkah | Hasil |
|---|---|
| `php -l` pada delapan file uji | bersih |
| `db:migrate main up --all --no-interaction` | `[success:up] 20261105_01_create_produk.php berhasil dijalankan` |
| `GET /05.01-crud-endpoint/specs/index.yaml` | `200`, YAML terbaca; Redocly lint `0 error`, `5 warning` aturan `4XX` karena respons error sengaja tidak didokumentasikan |
| `GET /05.01-crud-endpoint/specs/schemas/Produk.yaml`, `specs/paths/produk.yaml` | `200`, `$ref` relatif terbaca |
| `GET /05.01-crud-endpoint/specs/` | `200`, HTML termuat dengan `url: './index.yaml'` dan `validatorUrl: null`; pengecekan visual manual |
| `GET /05.01-crud-endpoint/produk` | `200`, `data` kosong dan `count` `0` |
| `POST /produk` body valid | `201`, `idProduk` `1`, `createdAt`/`updatedAt` terisi |
| `POST /produk` JSON rusak | `400`, `code` `produk/invalid-json` |
| `POST /produk` tanpa `nama`/`harga` | `422`, `data.errors.nama` dan `data.errors.harga` |
| `GET /produk?q=kopi&limit=10&offset=0` | `200`, satu baris, `count` `1` |
| `GET /produk?limit=abc` | `200`, limit dipaksa `1`, `count` tetap `2` |
| `GET /produk?limit=1000` | `200`, limit dipotong menjadi `100` |
| `GET /produk?offset=-1` | `200`, offset dipaksa `0` |
| `GET /produk/1` | `200`, detail produk |
| `PATCH /produk/1` body `{"stok":20}` | `200`, `stok` `20`, `updatedAt` berubah |
| `PATCH /produk/1` body tidak valid | `422`, dua error field |
| `GET /produk/999` | `404`, `code` `produk/not-found` |
| `GET /produk/abc` | `400`, `code` `produk/invalid-id` |
| `DELETE /produk/1` | `204` tanpa body |
| `GET /produk/1` setelah delete | `404` |
| `db:migrate main down --no-interaction` | `[success:down]`; tabel `produk` hilang |

Tabel `produk` dibatalkan dengan `db:migrate main down` setelah pengujian agar
database uji kembali bersih.

Belum diuji: pagination dengan data lebih dari satu halaman, penggantian
handler produksi, dan interaksi Swagger UI pada struktur `specs/` baru.
