---
id: database-koneksi
title: Koneksi Database
type: panduan
tags: [database, koneksi, manager, env, transaksi, query-mentah, multi-koneksi, mysql]
prerequisites: [instalasi]
verified: 2026-10-29
applies_to: Selvi 3.x
---

# Koneksi Database

Selvi menyimpan koneksi database di satu registry statis: `Selvi\Database\Manager`.
Aplikasi mendaftarkan koneksi sekali saat bootstrap, lalu seluruh fitur database
(query builder, model, migrasi, seeder) memakai **nama koneksi** itu.

Halaman ini hanya menyiapkan koneksi. Belum ada tabel yang dibuat dan belum ada
data yang dibaca.

## Prasyarat

- [Instalasi](../01-memulai/01-instalasi.md) sudah berhasil dan aplikasi bisa
  membalas request.
- Server MySQL atau MariaDB aktif.
- Ekstensi `mysqli` aktif. Driver yang tersedia saat ini hanya `mysql`.

## 1. Menyimpan konfigurasi di `.ENV`

Buat file `.ENV` di root proyek:

```text
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_USER=selvi
DB_PASS=rahasia
DB_NAME=selvi_app
```

- Formatnya `KEY=VALUE`, satu per baris. Baris berawalan `#` diabaikan.
- Tanda kutip di awal/akhir nilai dibuang, jadi `DB_NAME="selvi_app"` sama
  dengan `DB_NAME=selvi_app`.
- Jangan commit file ini; isinya kredensial.

## 2. Mendaftarkan koneksi

`app/Config/database.php`:

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

`index.php`:

```php
<?php

require __DIR__.'/vendor/autoload.php';

use Selvi\Env;

Env::load(__DIR__.'/.ENV');

require __DIR__.'/app/Config/exception.php';
require __DIR__.'/app/Config/database.php';
require __DIR__.'/app/Config/routes.php';

\Selvi\Framework::run();
```

Hasil: aplikasi berjalan seperti biasa. Bila kredensial atau nama database
salah, bootstrap langsung gagal dengan `DatabaseException` (bentuk responsnya di
*Bentuk error database*).

Hal yang perlu diketahui:

- `Env::load()` harus dipanggil sendiri; framework tidak membaca `.ENV` otomatis.
  File hanya dibaca sekali, pemanggilan kedua diabaikan.
- `main` adalah **nama koneksi**, bukan nama database. Nama yang sama dipakai
  lagi di `#[Table(schema: 'main')]`, `db:migrate main up`, dan
  `DB::connection('main')`.
- Kunci wajib: `host`, `username`, `password`, `database`. Kunci `driver`
  default `mysql`; `port` dan `socket` opsional.
- `Manager::add()` **langsung** membuat koneksi. Koneksi tidak ditunda sampai
  query pertama, sehingga kesalahan konfigurasi muncul saat bootstrap.
- Nama yang sudah terdaftar tidak ditimpa: `Manager::add('main', ...)` kedua
  kali diabaikan tanpa error.
- Urutan `require` penting. Muat `exception.php` sebelum `database.php`, supaya
  kegagalan koneksi tetap keluar sebagai JSON, bukan halaman error PHP.

## 3. Empat pintu masuk ke database

```php
use Selvi\Database\DB;
use Selvi\Database\Manager;

DB::table('kontak');      // QueryBuilder untuk tabel kontak
DB::connection('main');   // QueryBuilder, bukan objek koneksi
DB::schema();             // SchemaBuilder untuk operasi DDL
Manager::get('main');     // IConnection: koneksi sebenarnya
```

- `DB::table()` dan `DB::connection()` mengembalikan `QueryBuilder`, bukan
  `IConnection`. Ini sering tertukar karena namanya mirip.
- Tanpa nama (`DB::connection()`, `DB::schema()`), yang dipakai adalah
  `Manager::default()`, yaitu koneksi **pertama** yang didaftarkan. Bukan
  koneksi bernama `main` secara khusus.
- `Manager::get('nama')` melempar `InvalidArgumentException` bila nama belum
  terdaftar. Gunakan `Manager::has('nama')` bila ingin memeriksa lebih dulu.

Contoh query builder memakai koneksi default:

```php
$sql = DB::table('kontak')
    ->select('id_kontak', 'nm_kontak')
    ->where([['id_kontak', '=', 1]])
    ->getSql();
```

Hasil `$sql`:

```text
SELECT id_kontak, nm_kontak FROM kontak WHERE id_kontak = 1
```

`getSql()` hanya menyusun SQL dan tidak mengeksekusinya, jadi aman dipakai untuk
memastikan koneksi default sudah ter-resolve.

## 4. Query mentah

Untuk SQL yang tidak dicakup query builder, pakai koneksinya langsung:

```php
$result = Manager::get('main')->query('SELECT 1 AS ok');
$row = $result->row();
```

Hasil:

```json
{
    "ok": 1
}
```

- `query()` mengembalikan `IResult` untuk `SELECT`, dan `bool` untuk statement
  lain (`INSERT`, `UPDATE`, `DELETE`, DDL). Periksa dengan `instanceof IResult`.
- `rows()` mengembalikan `array` berisi `stdClass`, `row()` baris pertama, dan
  `num_rows()` jumlah baris.
- `result()` masih ada tetapi sudah deprecated; gunakan `rows()`.
- SQL di sini disusun manual, jadi Anda yang bertanggung jawab atas isinya.
  Jangan menyisipkan input user langsung ke string SQL.

## 5. Transaksi

Transaksi menempel pada koneksi, bukan pada `QueryBuilder`:

```php
$db = Manager::get('main');

$db->startTransaction();

try {
    $db->query("UPDATE stok SET jumlah = jumlah - 1 WHERE id_stok = 1");
    $db->query("INSERT INTO mutasi (id_stok, jumlah) VALUES (1, -1)");
    $db->commit();
} catch (\Throwable $e) {
    $db->rollback();
    throw $e;
}
```

- `startTransaction()`, `commit()`, dan `rollback()` mengembalikan `bool`.
- Bila memakai `QueryBuilder`, tetap ambil koneksi yang sama lewat
  `Manager::get('main')` untuk transaksi.
- DDL (`CREATE TABLE`, `ALTER TABLE`, ...) tidak bisa dibatalkan karena MySQL
  melakukan commit implisit.

## 6. Lebih dari satu koneksi

```php
Manager::add('report', [
    'driver' => 'mysql',
    'host' => '10.0.0.2',
    'username' => 'report',
    'password' => 'rahasia',
    'database' => 'laporan',
]);
```

Memilih koneksi pada query builder:

```php
DB::table('kontak')->useConnection('report')->get();
DB::connection('report')->table('kontak')->get();
```

- `useConnection()` menerima nama koneksi atau objek `IConnection`.
- Model memilih koneksi dari `#[Table(schema: 'report')]`.
- Join lintas koneksi tidak didukung; lakukan penggabungan data di PHP.

## 7. Bentuk error database

Query yang gagal melempar `DatabaseException`, dan handler bawaan mengubahnya
menjadi JSON:

```json
{
    "code": "database/error",
    "message": "Table 'selvi_app.tabel_yang_tidak_ada' doesn't exist",
    "sql": {
        "state": "42S02",
        "query": "SELECT * FROM tabel_yang_tidak_ada"
    }
}
```

- Status HTTP default `500`.
- `code` selalu `database/error`, `sql.state` adalah SQLSTATE dari driver, dan
  `sql.query` hanya terisi bila kegagalan terjadi saat mengeksekusi query.
- JSON ini hanya muncul bila `handler` exception sudah didaftarkan, seperti pada
  `app/Config/exception.php` di halaman [instalasi](../01-memulai/01-instalasi.md).
- Kegagalan koneksi (kredensial salah, database tidak ada) juga memakai bentuk
  JSON yang sama, tetapi `sql.query` bernilai `null` karena terjadi saat
  `Manager::add()`.

## Referensi API

| Simbol | Signature |
|---|---|
| `Manager::add()` | `add(string $name, array $config): void` |
| `Manager::get()` | `get(string $name): IConnection` |
| `Manager::default()` | `default(): ?IConnection` |
| `Manager::has()` | `has(string $name): bool` |
| `DB::table()` | `table(string $table): QueryBuilder` |
| `DB::connection()` | `connection(string $name = ''): QueryBuilder` |
| `DB::schema()` | `schema(string $connection = ''): SchemaBuilder` |
| `IConnection::query()` | `query(string $sql): IResult\|bool` |
| `IConnection::lastId()` | `lastId(): int` |
| `IConnection::getConfig()` | `getConfig(): array\|null` |
| `IConnection::connect()` | `connect(): bool` |
| `IConnection::disconnect()` | `disconnect(): bool` |
| `IConnection::startTransaction()` | `startTransaction(): bool` |
| `IConnection::commit()` | `commit(): bool` |
| `IConnection::rollback()` | `rollback(): bool` |
| `IResult::rows()` | `rows(): array\|bool\|null` |
| `IResult::row()` | `row(): stdClass\|bool\|null` |
| `IResult::num_rows()` | `num_rows(): int\|bool` |
| `Env::load()` | `load(string $path): void` |
| `Env::get()` | `get(string $key, $default = null)` |

Method pada `DB` tidak menulis return type eksplisit di source; nilainya sesuai
method yang dipanggil di dalamnya (`QueryBuilder` atau `SchemaBuilder`).

## Kesalahan umum

| Gejala | Penyebab | Perbaikan |
|---|---|---|
| `InvalidArgumentException: Koneksi 'main' tidak terdaftar.` | `app/Config/database.php` belum di-`require`, atau namanya salah | muat config sebelum route, cek ejaan nama koneksi |
| `RuntimeException: Koneksi database belum diatur.` | `DB::table()` dipakai padahal belum ada koneksi terdaftar | panggil `Manager::add()` lebih dulu |
| `InvalidArgumentException: Driver 'sqlsrv' belum punya implementasi IConnection.` | driver di luar `mysql` | pakai `mysql`, driver lain belum tersedia |
| Bootstrap gagal dengan `DatabaseException` (`Access denied`, `Unknown database`) | kredensial atau nama database salah; koneksi dibuka saat `Manager::add()` | perbaiki `.ENV`, ingat koneksi tidak ditunda |
| `Warning: Undefined array key "host"` | kunci wajib tidak ada di array config | lengkapi `host`, `username`, `password`, `database` |
| `Call to undefined method QueryBuilder::...` pada `DB::connection('main')` | `DB::connection()` mengembalikan `QueryBuilder`, bukan koneksi | pakai `Manager::get('main')` untuk method `IConnection` |
| `RuntimeException: Environment file not found` | path `.ENV` salah | pakai path absolut, mis. `__DIR__.'/.ENV'` |
| Error database tampil sebagai halaman HTML, bukan JSON | `Handler::setDefaultHandlers()` dan `Handler::listen()` belum dipanggil | muat `app/Config/exception.php` di bootstrap |
| `Env::load()` kedua tidak berefek | file hanya dibaca sekali per proses | selesaikan konfigurasi pada pemanggilan pertama |

## Sumber

- `src/Database/Manager.php` — registry koneksi.
- `src/Database/DB.php` — facade `connection()`, `table()`, `schema()`.
- `src/Database/Drivers/MySQL/MySQLConnection.php` — implementasi `IConnection`.
- `src/Database/Interfaces/IConnection.php` dan `IResult.php` — kontrak koneksi
  dan hasil query.
- `src/Env.php` — pembacaan `.ENV`.
- `src/Exception/DatabaseException.php` dan `src/Exception/Handler.php` — bentuk
  error database.
- `tests/02.01-koneksi/index.php` — semua contoh di halaman ini.

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 29 Oktober 2026 dengan container `mochrira/dev` (PHP 8.4.14) lewat
`http://localhost:8089/02.01-koneksi/`. Aplikasi uji memuat `vendor/` workspace,
jadi yang diuji adalah `src/` repo ini. `php -l` pada `index.php` juga bersih.

| Request | Status | Hasil |
|---|---|---|
| `GET /02.01-koneksi/` | `200` | `terdaftar: true`, `driver: mysql`, `host` dan `database` sesuai `.ENV` harness (`mariadb.database`, `test`) |
| `GET /02.01-koneksi/ping` | `200` | `{"ok": 1}` |
| `GET /02.01-koneksi/sql` | `200` | `SELECT id_kontak, nm_kontak FROM kontak WHERE id_kontak = 1` |
| `GET /02.01-koneksi/transaksi` | `200` | `{"start": true, "rollback": true}` |
| `GET /02.01-koneksi/tidak-terdaftar` | `200` | `InvalidArgumentException: Koneksi 'lain' tidak terdaftar.` |
| `GET /02.01-koneksi/error` | `500` | JSON `code: database/error`, `sql.state: 42S02`, `sql.query` terisi |

Endpoint `/error` sengaja menanyakan tabel yang tidak ada, untuk membuktikan
bentuk error di *Bentuk error database*. Tabel `kontak` tidak perlu ada karena `/sql` hanya
menyusun SQL.

Satu hal yang belum diuji: kegagalan saat `Manager::add()` (kredensial salah).
Perilaku itu disimpulkan dari `MySQLConnection::__construct()` yang langsung
memanggil `connect()`.
