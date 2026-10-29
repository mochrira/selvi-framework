# Referensi: `Selvi\Database\Builder\DDL\SchemaBuilder`

Penjalan operasi DDL. Instance dibuat lewat `DB::schema()`, atau lewat
`Selvi\Database\Schema` yang sudah terikat ke satu koneksi (dipakai file
migrasi).

Aturan yang berlaku:

- Method eksekusi mengembalikan `bool`. Kegagalan SQL dilempar sebagai
  `Selvi\Exception\DatabaseException`, bukan nilai `false`.
- Pasangan `compile*()` menyusun SQL **tanpa** mengeksekusi.
- Bila Grammar mengembalikan beberapa statement (sebagian driver), semuanya
  dijalankan berurutan dan berhenti pada kegagalan pertama.

Pemakaian ada di [skema tabel](../02-database/02-skema-tabel.md).

## Method instance

### `useConnection($connection)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$connection` | `string\|IConnection` | wajib | nama koneksi terdaftar, atau objek koneksi langsung |

**Kembalian:** instance `SchemaBuilder` (dapat dirangkai).

### `connection()`

Tidak ada parameter.

**Kembalian:** `Selvi\Database\Interfaces\IConnection`.

Melempar `RuntimeException: Koneksi database belum diatur.` bila belum ada
koneksi.

### `create($table, $definition)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$table` | `string` | wajib | nama tabel |
| `$definition` | `Closure(Blueprint): void` | `null` | pengisi definisi kolom |

**Kembalian:** `bool`.

### `compileCreate($table, $definition)`

Parameter sama dengan `create()`.

**Kembalian:** `string` — `CREATE TABLE [IF NOT EXISTS] ...`.

### `drop($table, $ifExists)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$table` | `string` | wajib | nama tabel |
| `$ifExists` | `bool` | `true` | menambahkan `IF EXISTS` |

**Kembalian:** `bool`.

### `compileDrop($table, $ifExists)`

Parameter sama dengan `drop()`.

**Kembalian:** `string` — `DROP TABLE [IF EXISTS] ...`.

### `rename($table, $newTable)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$table` | `string` | wajib | nama tabel lama |
| `$newTable` | `string` | wajib | nama tabel baru |

**Kembalian:** `bool`.

### `compileRename($table, $newTable)`

Parameter sama dengan `rename()`.

**Kembalian:** `string` — `ALTER TABLE ... RENAME TO ...`.

### `truncate($table)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$table` | `string` | wajib | nama tabel |

**Kembalian:** `bool`.

### `compileTruncate($table)`

Parameter sama dengan `truncate()`.

**Kembalian:** `string` — `TRUNCATE TABLE ...`.

### `alter($table, $definition)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$table` | `string` | wajib | nama tabel yang diubah |
| `$definition` | `Closure(AlterBlueprint): void` | wajib | daftar operasi perubahan |

**Kembalian:** `bool`.

### `compileAlter($table, $definition)`

Parameter sama dengan `alter()`.

**Kembalian:** `string|array<string>`.

Melempar `InvalidArgumentException: Definisi alter untuk tabel x tidak memiliki
operasi apa pun.` bila closure tidak mencatat operasi.

## Sumber

- `src/Database/Builder/DDL/SchemaBuilder.php`
- `src/Database/Schema.php` — facade yang mendelegasikan ke kelas ini
- `src/Database/Drivers/MySQL/MySQLGrammar.php` — perender SQL
- `tests/02.02-skema-tabel/index.php` — aplikasi uji

## Status verifikasi

Diuji 29 Oktober 2026 lewat `http://localhost:8089/02.02-skema-tabel/`:

| Method | Hasil |
|---|---|
| `create()`, `drop()`, `rename()`, `truncate()`, `alter()` | semua mengembalikan `true` |
| `compileCreate()`, `compileAlter()` | SQL sama persis dengan yang dijalankan |
| `useConnection('main')` | koneksi ter-resolve |

Belum diuji: `useConnection()` dengan objek `IConnection`, dan jalur
multi-statement (`array`), karena Grammar MySQL selalu mengembalikan satu
string. Keduanya disimpulkan dari kode.
