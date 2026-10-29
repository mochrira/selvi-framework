# Referensi: `Selvi\Database\Builder\DDL\AlterBlueprint`

Kolektor operasi untuk `SchemaBuilder::alter()`. Instance dibuat oleh
`SchemaBuilder` lalu diteruskan ke closure.

Aturan yang berlaku:

- Operasi dicatat sesuai urutan pemanggilan, lalu Grammar merendernya. MySQL
  menggabungkan semuanya menjadi satu `ALTER TABLE`.
- Minimal satu operasi; tanpa operasi `compileAlter()` melempar
  `InvalidArgumentException`.
- Nama index dan foreign key wajib, karena nama itulah yang dipakai lagi saat
  menghapusnya. Nama primary key opsional.
- Aksi referensial dinormalkan ke huruf besar dan divalidasi terhadap
  `CASCADE`, `SET NULL`, `RESTRICT`, `NO ACTION`, dan `SET DEFAULT`.
- Method tipe kolom sama seperti
  [`Blueprint`](11-blueprint.md), tetapi dicatat sebagai operasi `add`.
  `change()` mengubahnya menjadi `modify`.

Pemakaian ada di [skema tabel](../02-database/02-skema-tabel.md).

## Method instance

### `__construct($table)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$table` | `string` | wajib | nama tabel yang diubah |

Dipakai internal oleh `SchemaBuilder::alter()`.

**Kembalian:** instance `AlterBlueprint`.

### `dropColumn($name)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | wajib | nama kolom yang dihapus |

**Kembalian:** instance `AlterBlueprint`. SQL: `DROP COLUMN x`.

### `renameColumn($from, $to)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$from` | `string` | wajib | nama kolom lama |
| `$to` | `string` | wajib | nama kolom baru |

**Kembalian:** instance `AlterBlueprint`. SQL: `RENAME COLUMN a TO b` (butuh
MySQL 8.0+ / MariaDB 10.5.2+).

Tipe dan atribut kolom tidak berubah; hanya namanya.

### `index($columns, $name)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$columns` | `array<string>` | wajib | kolom yang diindeks |
| `$name` | `string` | wajib | nama index |

**Kembalian:** instance `AlterBlueprint`. SQL: `ADD INDEX nama (a, b)`.

### `unique($columns, $name)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$columns` | `array<string>` | wajib | kolom yang diindeks |
| `$name` | `string` | wajib | nama index unik |

**Kembalian:** instance `AlterBlueprint`. SQL: `ADD UNIQUE INDEX nama (a, b)`.

### `dropIndex($name)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | wajib | nama index |

**Kembalian:** instance `AlterBlueprint`. SQL: `DROP INDEX nama`.

### `primary($columns, $name)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$columns` | `array<string>` | wajib | kolom primary key, boleh lebih dari satu |
| `$name` | `string|null` | `null` | nama constraint; diabaikan MySQL karena namanya selalu `PRIMARY` |

**Kembalian:** instance `AlterBlueprint`. SQL: `ADD [CONSTRAINT n] PRIMARY KEY (a, b)`.

### `dropPrimary($name)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string|null` | `null` | nama constraint; hanya untuk driver yang menuntutnya |

**Kembalian:** instance `AlterBlueprint`. SQL: `DROP PRIMARY KEY`.

Gagal di MySQL bila kolom primary key masih `AUTO_INCREMENT` (error 1075).

### `foreign($columns, $foreignTable, $references, $name, $onDelete, $onUpdate)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$columns` | `array<string>` | wajib | kolom di tabel ini |
| `$foreignTable` | `string` | wajib | tabel yang dirujuk |
| `$references` | `array<string>` | wajib | kolom di tabel rujukan |
| `$name` | `string` | wajib | nama constraint |
| `$onDelete` | `string|null` | `null` | aksi `ON DELETE` |
| `$onUpdate` | `string|null` | `null` | aksi `ON UPDATE` |

**Kembalian:** instance `AlterBlueprint`. SQL:
`ADD CONSTRAINT nama FOREIGN KEY (a) REFERENCES t (b) [ON DELETE x] [ON UPDATE y]`.

### `dropForeign($name)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | wajib | nama constraint foreign key |

**Kembalian:** instance `AlterBlueprint`. SQL: `DROP FOREIGN KEY nama`.

### `getTable()`

**Kembalian:** `string` — nama tabel. Dipakai Grammar.

### `getOperations()`

**Kembalian:** `array<int, array<string, mixed>>` — daftar node operasi kanonik
sesuai urutan pemanggilan. Dipakai Grammar.

### Method tipe kolom

`integer()`, `bigInteger()`, `string()`, `text()`, `boolean()`, `decimal()`,
`float()`, `date()`, dan `datetime()` berasal dari trait `HasColumnTypes`.
Signature dan tipe MySQL-nya sama persis dengan
[`Blueprint`](11-blueprint.md#method-instance); perbedaannya hanya pada hasil
pencatatan operasi:

- tanpa `change()` → `ADD COLUMN`
- dengan `change()` → `MODIFY COLUMN`
- `after()` / `first()` → menambahkan `AFTER x` / `FIRST`

**Kembalian:** `ColumnClause`.

## Sumber

- `src/Database/Builder/DDL/AlterBlueprint.php`
- `src/Database/Builder/DDL/HasColumnTypes.php` — method tipe kolom
- `src/Database/Interfaces/IAlterBlueprint.php` — kontrak untuk Grammar
- `src/Database/Drivers/MySQL/MySQLGrammar.php` — `compileAlterTable()`
- `tests/02.02-skema-tabel/index.php` — aplikasi uji

## Status verifikasi

Diuji 29 Oktober 2026 lewat `http://localhost:8089/02.02-skema-tabel/`:

| Method | Hasil |
|---|---|
| method tipe kolom (`add`, `change`) | `ADD COLUMN` dan `MODIFY COLUMN` berhasil |
| `after()`, `first()` | urutan kolom sesuai posisi yang diminta |
| `renameColumn()`, `dropColumn()` | berhasil |
| `index()`, `unique()`, `dropIndex()` | berhasil |
| `primary()`, `dropPrimary()` | berhasil pada tabel tanpa `AUTO_INCREMENT` |
| `foreign()`, `dropForeign()` | berhasil, termasuk `ON DELETE SET NULL` |

Belum diuji: penolakan operasi kosong, nama index/foreign key kosong, dan aksi
referensial yang tidak dikenal. Ketiganya disimpulkan dari kode.
