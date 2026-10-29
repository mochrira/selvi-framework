---
id: database-skema-tabel
title: Skema Tabel
type: panduan
tags: [database, ddl, create-table, alter-table, index, primary-key, foreign-key, mysql]
prerequisites: [database-koneksi]
verified: 2026-10-29
applies_to: Selvi 3.x
---

# Skema Tabel

Struktur tabel dibuat dan diubah lewat `SchemaBuilder`. Definisi ditulis sebagai
method PHP, lalu Grammar driver menerjemahkannya menjadi SQL — saat ini hanya
dialek MySQL/MariaDB. Tidak perlu menulis `CREATE TABLE` manual.

## Prasyarat

- [Koneksi database](01-koneksi.md) sudah berjalan.

## 1. Membuat tabel

```php
use Selvi\Database\Builder\DDL\Blueprint;
use Selvi\Database\DB;

DB::schema()->create('kontak', function (Blueprint $table) {
    $table->integer('id_kontak')->key()->autoIncrement();
    $table->string('nm_kontak', 100);
    $table->string('nomor', 20)->nullable();
});
```

Hasil: tabel dibuat, dan `create()` mengembalikan `true`. SQL yang dijalankan:

```sql
CREATE TABLE IF NOT EXISTS kontak (id_kontak INT NOT NULL AUTO_INCREMENT PRIMARY KEY, nm_kontak VARCHAR(100) NOT NULL, nomor VARCHAR(20))
```

- `IF NOT EXISTS` aktif secara default. Matikan lewat
  `$table->ifNotExists(false)` di dalam closure.
- Urutan kolom mengikuti urutan pemanggilan.
- `nomor` memakai `nullable()` karena kontak boleh tidak punya nomor; tanpa
  modifier itu kolomnya otomatis `NOT NULL`.
- Nama kolom yang sama dua kali melempar `InvalidArgumentException`.
- Kegagalan SQL (mis. tabel sudah ada saat `IF NOT EXISTS` dimatikan) dilempar
  sebagai `DatabaseException`, bukan nilai `false`.

## 2. Tipe kolom

Semua tipe di bawah tersedia di `Blueprint` maupun `AlterBlueprint`:

| Method | Signature | MySQL |
|---|---|---|
| `integer()` | `integer($name, bool $nullable = false)` | `INT` |
| `bigInteger()` | `bigInteger($name, bool $nullable = false)` | `BIGINT` |
| `string()` | `string($name, int $length = 255, bool $nullable = false)` | `VARCHAR(n)` |
| `text()` | `text($name, bool $nullable = false)` | `TEXT` |
| `boolean()` | `boolean($name, bool $nullable = false)` | `TINYINT(1)` |
| `decimal()` | `decimal($name, int $precision = 10, int $scale = 0, bool $nullable = false)` | `DECIMAL(p, s)` |
| `float()` | `float($name, bool $nullable = false)` | `DOUBLE` |
| `date()` | `date($name, bool $nullable = false)` | `DATE` |
| `datetime()` | `datetime($name, bool $nullable = false)` | `DATETIME` |

Tipe tidak dikenal (mis. dari grammar driver lain) melempar
`InvalidArgumentException: Tipe kolom tidak dikenal: ...` alih-alih menghasilkan
SQL yang salah.

## 3. Modifier kolom

Setiap method tipe mengembalikan `ColumnClause`, sehingga modifier dirantai
langsung:

| Modifier | Arti |
|---|---|
| `key(bool $flag = true)` | `PRIMARY KEY` |
| `autoIncrement(bool $flag = true)` | `AUTO_INCREMENT` |
| `nullable(bool $flag = true)` | menghilangkan `NOT NULL` |
| `default(mixed $value)` | `DEFAULT <nilai tersanitasi>` |
| `unique(bool $flag = true)` | `UNIQUE` |
| `length(int $length)` | mengubah panjang `VARCHAR` |
| `change(bool $flag = true)` | mengubah operasi `add` menjadi `modify` (khusus `alter()`) |
| `after(string $column)` | menempatkan kolom setelah kolom lain (khusus `alter()`) |
| `first()` | menempatkan kolom paling depan (khusus `alter()`) |

Urutan atribut pada SQL selalu: `nama tipe`, `NOT NULL`, `DEFAULT`,
`AUTO_INCREMENT`, `PRIMARY KEY`, `UNIQUE`.

`default()` disanitasi driver, bukan dirender apa adanya: `default(true)`
menjadi `DEFAULT 1`, `default('aktif')` menjadi `DEFAULT 'aktif'`, dan
`default(null)` tidak menghasilkan klausa `DEFAULT` sama sekali.

`after()` dan `first()` diabaikan di `create()` karena urutan kolom di sana
ditentukan urutan pemanggilan; keduanya saling eksklusif.

## 4. Melihat SQL tanpa mengeksekusi

Setiap operasi punya pasangan `compile*()` yang mengembalikan SQL saja:

```php
$schema = DB::schema();

$schema->compileCreate('kontak', function (Blueprint $table) { /* ... */ });
$schema->compileDrop('kontak');
$schema->compileRename('kontak', 'kontak_lama');
$schema->compileTruncate('kontak_lama');
$schema->compileAlter('kontak', function (AlterBlueprint $table) { /* ... */ });
```

`compileAlter()` dapat mengembalikan `string` **atau** `array<string>`.
Grammar MySQL selalu menghasilkan satu string, tetapi driver yang butuh beberapa
statement (mis. `ALTER TABLE` di SQL Server) boleh mengembalikan array. Method
`alter()` yang menjalankan semuanya berurutan dan berhenti pada kegagalan
pertama.

## 5. Mengubah tabel

```php
use Selvi\Database\Builder\DDL\AlterBlueprint;

DB::schema()->alter('kontak', function (AlterBlueprint $table) {
    $table->string('email', 100)->nullable()->after('nomor');
    $table->unique(['nomor'], 'uniq_kontak_nomor');
});
```

Hasil: satu statement, karena MySQL bisa menggabungkan beberapa operasi.

```sql
ALTER TABLE kontak ADD COLUMN email VARCHAR(100) AFTER nomor, ADD UNIQUE INDEX uniq_kontak_nomor (nomor)
```

Menambah relasi ke tabel lain menuntut tabel tujuannya sudah ada:

```php
DB::schema()->create('grup', function (Blueprint $table) {
    $table->integer('id_grup')->key()->autoIncrement();
    $table->string('nm_grup', 50);
});

DB::schema()->alter('kontak', function (AlterBlueprint $table) {
    $table->integer('id_grup')->nullable()->after('email');
    $table->foreign(['id_grup'], 'grup', ['id_grup'], 'fk_kontak_grup', onDelete: 'SET NULL');
});
```

```sql
ALTER TABLE kontak ADD COLUMN id_grup INT AFTER email, ADD CONSTRAINT fk_kontak_grup FOREIGN KEY (id_grup) REFERENCES grup (id_grup) ON DELETE SET NULL
```

Operasi yang tersedia di `AlterBlueprint`:

| Kebutuhan | Pemanggilan |
|---|---|
| Tambah kolom | `$table->string('email', 100)->nullable();` |
| Ubah definisi kolom | `$table->string('nm_kontak', 200)->change();` |
| Hapus kolom | `$table->dropColumn('email');` |
| Ganti nama kolom | `$table->renameColumn('nm_kontak', 'nama_kontak');` |
| Index | `$table->index(['nm_kontak'], 'idx_kontak_nama');` |
| Index unik | `$table->unique(['nomor'], 'uniq_kontak_nomor');` |
| Hapus index | `$table->dropIndex('idx_kontak_nama');` |
| Primary key | `$table->primary(['id_kontak']);` |
| Hapus primary key | `$table->dropPrimary();` |
| Foreign key | `$table->foreign(['id_grup'], 'grup', ['id_grup'], 'fk_kontak_grup', onDelete: 'SET NULL');` |
| Hapus foreign key | `$table->dropForeign('fk_kontak_grup');` |
| Posisi kolom | `->after('nomor')` atau `->first()` |

Aturan yang penting:

- `change()` hanya mengubah peran operasi, **bukan** menambal satu atribut.
  MySQL menulis ulang definisi kolom, jadi semua atribut harus disebut lagi;
  yang tidak disebut akan hilang.
- Nama index dan foreign key **wajib**. Nama otomatis dari driver tidak bisa
  ditebak, padahal nama itu yang dipakai saat `dropIndex()`/`dropForeign()`.
- Nama primary key opsional: MySQL selalu menamainya `PRIMARY`, dan parameter
  nama hanya berguna untuk driver yang menuntutnya.
- Closure `alter()` wajib menghasilkan minimal satu operasi. Tanpa operasi,
  `InvalidArgumentException` dilempar karena `ALTER TABLE` kosong bukan
  statement yang valid.
- Aksi referensial divalidasi terhadap `CASCADE`, `SET NULL`, `RESTRICT`,
  `NO ACTION`, dan `SET DEFAULT`.

## 6. Menghapus, mengganti nama, mengosongkan

```php
$schema = DB::schema();

$schema->drop('kontak');                  // DROP TABLE IF EXISTS kontak
$schema->rename('kontak', 'kontak_lama'); // ALTER TABLE kontak RENAME TO kontak_lama
$schema->truncate('kontak_lama');         // TRUNCATE TABLE kontak_lama
```

- `drop()` memakai `IF EXISTS` secara default; matikan dengan
  `drop('tabel', false)`.
- `rename()` memakai bentuk ANSI `ALTER TABLE ... RENAME TO ...`.
- `truncate()` mengosongkan isi tanpa menghapus struktur dan tidak bisa
  dibatalkan.

## 7. Batasan yang perlu diketahui

- **Index, primary key, dan foreign key hanya bisa dibuat lewat `alter()`**,
  belum inline di dalam `create()`. Polanya: `create()` lalu `alter()` dengan
  operasi yang sama seperti contoh di *Mengubah tabel*.
- DDL tidak transaksional. MySQL melakukan commit implisit, jadi kegagalan di
  tengah rangkaian operasi meninggalkan struktur setengah jadi.
- `ADD COLUMN ... NOT NULL` tanpa `default()` gagal pada tabel yang sudah berisi
  data (strict mode). Pakai `nullable()` atau `default()`.
- `DROP PRIMARY KEY` gagal bila kolomnya `AUTO_INCREMENT` (error 1075); ubah
  dulu kolom tersebut lewat `change()` tanpa `autoIncrement()`.
- MySQL tidak punya `CREATE INDEX IF NOT EXISTS`; menambah index yang sudah ada
  menghasilkan error SQL.
- Foreign key menuntut tipe kolom identik dengan kolom yang dirujuk, kolom
  rujukan terindeks (primary key sudah), dan semua data lama cocok.
- `ON DELETE SET DEFAULT` lolos validasi builder tetapi ditolak InnoDB.
- `Schema` (facade yang dipakai file migrasi) mengekspos method yang sama:
  `create()`, `drop()`, `rename()`, `truncate()`, `alter()`, plus `table()`
  untuk DML.

## Referensi API

| Simbol | Signature |
|---|---|
| `DB::schema()` | `schema(string $connection = ''): SchemaBuilder` |
| `SchemaBuilder::create()` | `create(string $table, ?Closure $definition = null): bool` |
| `SchemaBuilder::drop()` | `drop(string $table, bool $ifExists = true): bool` |
| `SchemaBuilder::rename()` | `rename(string $table, string $newTable): bool` |
| `SchemaBuilder::truncate()` | `truncate(string $table): bool` |
| `SchemaBuilder::alter()` | `alter(string $table, Closure $definition): bool` |
| `SchemaBuilder::compileCreate()` | `compileCreate(string $table, ?Closure $definition = null): string` |
| `SchemaBuilder::compileDrop()` | `compileDrop(string $table, bool $ifExists = true): string` |
| `SchemaBuilder::compileRename()` | `compileRename(string $table, string $newTable): string` |
| `SchemaBuilder::compileTruncate()` | `compileTruncate(string $table): string` |
| `SchemaBuilder::compileAlter()` | `compileAlter(string $table, Closure $definition): string\|array` |
| `Blueprint::ifNotExists()` | `ifNotExists(bool $flag = true): static` |
| `ColumnClause::default()` | `default(mixed $value): static` |
| `ColumnClause::after()` | `after(string $column): static` |
| `AlterBlueprint::index()` | `index(array $columns, string $name): static` |
| `AlterBlueprint::unique()` | `unique(array $columns, string $name): static` |
| `AlterBlueprint::primary()` | `primary(array $columns, ?string $name = null): static` |
| `AlterBlueprint::foreign()` | `foreign(array $columns, string $foreignTable, array $references, string $name, ?string $onDelete = null, ?string $onUpdate = null): static` |

## Kesalahan umum

| Gejala | Penyebab | Perbaikan |
|---|---|---|
| `InvalidArgumentException: Kolom 'x' didefinisikan lebih dari sekali pada tabel y.` | nama kolom duplikat di closure `create()` | pakai nama berbeda |
| `InvalidArgumentException: Tipe kolom tidak dikenal: x` | tipe tidak ada di grammar driver | pakai tipe yang didukung (lihat *Tipe kolom*) |
| `InvalidArgumentException: Definisi alter untuk tabel x tidak memiliki operasi apa pun.` | closure `alter()` kosong | tambahkan minimal satu operasi |
| `InvalidArgumentException: Nama index tidak boleh kosong.` | `index()`/`dropIndex()` tanpa nama | sebutkan nama index |
| `InvalidArgumentException: Nama foreign key tidak boleh kosong.` | `foreign()`/`dropForeign()` tanpa nama | sebutkan nama constraint |
| `DatabaseException` 1075 saat `dropPrimary()` | kolom primary key masih `AUTO_INCREMENT` | `change()` kolom tanpa `autoIncrement()` lebih dulu |
| `ADD COLUMN` gagal: `Invalid use of NULL value` / field tanpa default | kolom `NOT NULL` ditambahkan pada tabel berisi data | pakai `nullable()` atau `default()` |
| `CREATE TABLE` tidak berefek meski sudah dipanggil | `IF NOT EXISTS` bawaan, tabel sudah ada | hapus dulu atau panggil `ifNotExists(false)` |
| Error saat `foreign()`, padahal SQL benar | tipe kolom tidak identik atau data lama tidak cocok | samakan tipe dan bersihkan data |

## Sumber

- `src/Database/Builder/DDL/SchemaBuilder.php` — eksekusi dan `compile*()`.
- `src/Database/Builder/DDL/Blueprint.php` — kolektor kolom `create()`.
- `src/Database/Builder/DDL/AlterBlueprint.php` — operasi `alter()`.
- `src/Database/Builder/DDL/Clauses/ColumnClause.php` — modifier kolom.
- `src/Database/Builder/DDL/HasColumnTypes.php` — daftar tipe kolom.
- `src/Database/Drivers/MySQL/MySQLGrammar.php` — rendering SQL.
- `src/Database/Schema.php` — facade yang dipakai file migrasi.
- `tests/02.02-skema-tabel/index.php` — semua contoh di halaman ini.

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 29 Oktober 2026 dengan container `mochrira/dev` (PHP 8.4.14) dan server
MariaDB 10.7.8 lewat `http://localhost:8089/02.02-skema-tabel/`. `php -l` pada
`index.php` bersih.

Aplikasi uji memakai prefiks `demo_` (`demo_kontak`, `demo_grup`) supaya tabel
`kontak` milik harness `tests/app/` tidak tertimpa. Selain nama tabel, SQL yang
dihasilkan sama dengan contoh di halaman ini.

| Request | Status | Hasil |
|---|---|---|
| `GET /02.02-skema-tabel/` | `200` | empat statement SQL sesuai *Membuat tabel* dan *Mengubah tabel*, termasuk `AFTER nomor` dan `ON DELETE SET NULL` |
| `GET /02.02-skema-tabel/jalankan` | `200` | `drop_kontak`, `drop_grup`, `create_grup`, `create_kontak`, `alter_email`, `alter_grup` semuanya `true` |
| `GET /02.02-skema-tabel/struktur` | `200` | kolom `demo_kontak` berurutan `id_kontak`, `nm_kontak`, `nomor`, `email`, `id_grup`; index `PRIMARY`, `uniq_demo_kontak_nomor`, `fk_demo_kontak_grup` |
| `GET /02.02-skema-tabel/tipe` | `200` | sembilan tipe kolom terender benar, `length(30)` menggantikan panjang awal, `ifNotExists(false)` menghapus `IF NOT EXISTS` |
| `GET /02.02-skema-tabel/sementara` | `200` | `modify`, `add_first`, `rename_column`, `unique`, `drop_index`, `drop_column`, `rename`, `truncate`, `drop` semuanya `true` |
| `GET /02.02-skema-tabel/kunci` | `200` | `primary` pada dua kolom dan `drop_primary` berhasil |
| `GET /02.02-skema-tabel/hapus` | `200` | `drop_foreign`, `drop_kontak`, `drop_grup` bernilai `true` |

Urutan uji: `/` → `/jalankan` → `/struktur` → `/tipe` → `/sementara` → `/kunci` →
`/hapus`. Setelah rangkaian itu, `SHOW TABLES` di database uji hanya berisi
tabel milik harness (`_migration`, `kontak`, `departemen`, `profesi`,
`transaksi`, `unit`) — tidak ada sisa tabel `demo_`.

Yang belum diuji: kasus error di *Batasan yang perlu diketahui* (mis. `DROP PRIMARY KEY` pada kolom
`AUTO_INCREMENT`, `ADD COLUMN NOT NULL` pada tabel berisi data). Bagian itu
disimpulkan dari aturan MySQL, bukan dari uji di repo ini.
