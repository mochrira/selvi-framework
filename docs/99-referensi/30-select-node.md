---
id: ref-select-node
title: Selvi\Database\Builder\DML\Clauses\SelectNode
type: referensi
class: Selvi\Database\Builder\DML\Clauses\SelectNode
tags: [database, dml, select, ast, function, literal, raw]
prerequisites: [database-membaca-data]
verified: 2026-11-01
applies_to: Selvi 3.x
---

# Referensi: `Selvi\Database\Builder\DML\Clauses\SelectNode`

Pabrik node AST untuk `SELECT`. Node berupa array biasa; Grammar setiap driver
yang merendernya menjadi SQL.

Aturan yang berlaku:

- Semua node punya `type`.
- `column` memakai nama kolom apa adanya (identifier belum di-quote otomatis).
- `literal` disanitasi Grammar (MySQL: `MySQLSanitizer`).
- `raw` tidak disanitasi; hanya untuk SQL yang memang ditulis developer.
- `alias` hanya dirender pada node top-level; argumen fungsi dirender tanpa
  alias.
- Node lama tanpa `type` tetap dianggap `column` oleh Grammar.

Pemakaian ada di [membaca data](../04-database/03-membaca-data.md) dan
[`QueryBuilder`](16-query-builder.md).

## Catatan attribute

PHP hanya mengizinkan constant expression di argumen attribute. Pemanggilan
method seperti `SelectNode::function(...)` **tidak bisa** ditulis langsung di
`#[Column]` atau `#[Aggregate]`. Ada dua cara memakai fungsi di attribute.

### 1. String resolver (disarankan)

Attribute menyimpan nama static method; method itulah yang memanggil factory.

```php
#[Column('nm_kontak', expression: 'namaKontak')]
public ?string $nmKontak;

public static function namaKontak(array $context): array {
    return SelectNode::function('COALESCE', [
        SelectNode::column($context['column'], $context['source']),
        SelectNode::literal('Tanpa Nama'),
    ]);
}
```

`ModelQuery` memanggil resolver dengan context `alias`, `column`, `source`,
`prefix`, dan `model`. Detail di
[`Column`](25-column.md#expression) dan [`Aggregate`](28-aggregate.md#expression).

### 2. Array node

```php
#[Column('nm_kontak', expression: [
    'type' => 'function',
    'name' => 'COALESCE',
    'arguments' => [
        ['type' => 'column', 'column' => 'nm_kontak'],
        ['type' => 'literal', 'value' => 'Tanpa Nama'],
    ],
])]
```

SQL mentah di attribute juga berupa node array `['type' => 'raw', 'sql' => ...]`.
Factory `SelectNode` tetap dipakai di kode builder, mis.
`selectFunction()`/`selectUsing()`, dan di dalam resolver.

## Konstanta

| Konstanta | Nilai |
|---|---|
| `TYPE_COLUMN` | `column` |
| `TYPE_FUNCTION` | `function` |
| `TYPE_LITERAL` | `literal` |
| `TYPE_RAW` | `raw` |

## Method statis

### `SelectNode::column($column, $table, $alias)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$column` | `string` | wajib | nama kolom; tidak boleh kosong |
| `$table` | `?string` | `null` | nama/alias tabel |
| `$alias` | `?string` | `null` | alias output |

**Kembalian:** `array`:

```php
[
    'type' => 'column',
    'table' => null,
    'column' => 'nm_kontak',
    'alias' => null,
]
```

### `SelectNode::function($name, $arguments, $alias)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | wajib | identifier fungsi, mis. `COALESCE` |
| `$arguments` | `array` | `[]` | list node (`SelectNode`) |
| `$alias` | `?string` | `null` | alias output |

**Kembalian:** `array`:

```php
[
    'type' => 'function',
    'name' => 'COALESCE',
    'arguments' => [/* node */],
    'alias' => 'nama',
]
```

Nama wajib cocok dengan `/^[A-Za-z_][A-Za-z0-9_]*$/`. Semua argumen harus node
array. Nama dirender uppercase oleh Grammar.

### `SelectNode::literal($value, $alias)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$value` | `mixed` | wajib | scalar atau `null` |
| `$alias` | `?string` | `null` | alias output |

**Kembalian:** `array` bertipe `literal`. String literal **wajib** lewat method
ini; string biasa di `select()` dianggap nama kolom.

### `SelectNode::raw($sql, $alias)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$sql` | `string` | wajib | SQL mentah; tidak boleh kosong |
| `$alias` | `?string` | `null` | alias output |

**Kembalian:** `array` bertipe `raw`. Tidak disanitasi; jangan memakai input
user.

### `SelectNode::star()`

**Kembalian:** node `raw('*')`, dipakai untuk `COUNT(*)`.

## Contoh

```php
use Selvi\Database\Builder\DML\Clauses\SelectNode;

DB::table('kontak')
    ->select(
        'id_kontak',
        SelectNode::function('COALESCE', [
            SelectNode::column('nm_kontak'),
            SelectNode::literal('Tanpa Nama'),
        ], 'nama'))
    ->selectFunction('COUNT', [SelectNode::star()], 'total');
```

SQL:

```sql
SELECT id_kontak, COALESCE(nm_kontak, 'Tanpa Nama') AS nama, COUNT(*) AS total FROM kontak
```

## Sumber

- [src/Database/Builder/DML/Clauses/SelectNode.php](../../src/Database/Builder/DML/Clauses/SelectNode.php)
- [src/Database/Builder/DML/SelectBuilder.php](../../src/Database/Builder/DML/SelectBuilder.php)
- [src/Database/Drivers/MySQL/MySQLGrammar.php](../../src/Database/Drivers/MySQL/MySQLGrammar.php) — renderer node
- [src/Database/Interfaces/IQueryBuilder.php](../../src/Database/Interfaces/IQueryBuilder.php) — `selectFunction()`/`selectRaw()`
- [tests/04.03-membaca-data/index.php](../../tests/04.03-membaca-data/index.php) — aplikasi uji

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 1 November 2026 lewat `tests/04.03-membaca-data` (PHP 8.4.14,
MariaDB 10.7.8):

| Kasus | Hasil |
|---|---|
| `column` | `nm_kontak` |
| `literal('Tanpa Nama')` | `'Tanpa Nama'` |
| `literal(null)` | `NULL` |
| `function('COALESCE', ...)` | `COALESCE(nm_kontak, 'Tanpa Nama')` |
| `function('CONCAT', ...)` | `CONCAT(id_kontak, '#', nm_kontak)` |
| `function('NOW')` tanpa argumen | `NOW()` |
| `star()` di `COUNT` | `COUNT(*)` |
| fungsi bertingkat `COALESCE(IFNULL(...))` | sesuai |
| nama fungsi tidak valid | `InvalidArgumentException` |
| `raw('')` | `InvalidArgumentException` |

Belum diuji: node dengan alias di dalam argumen fungsi, `raw` di dalam fungsi
bertingkat, dan tipe node yang tidak dikenal.
