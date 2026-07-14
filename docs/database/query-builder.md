# Query Builder

Selvi provides a fluent query builder interface through the `Schema` class.

## Getting a Schema Instance

```php
use Selvi\Database\Manager;

$db = Manager::get('main');
```

## SELECT Queries

### Basic Select

```php
// SELECT * FROM produk
$result = $db->get('produk');

// All rows as array of stdClass objects
$rows = $result->result();

// Single row as stdClass
$row = $result->row();

// Number of rows
$count = $result->num_rows();
```

### Select Specific Columns

```php
// SELECT idProduk, nmProduk FROM produk
$db->select(['idProduk', 'nmProduk'])
    ->get('produk');
```

Using a string:

```php
$db->select('idProduk, nmProduk, harga')
    ->get('produk');
```

### WHERE Conditions

```php
// WHERE status = 'active'
$db->where([['status', 'active']])->get('produk');

// Multiple conditions (AND)
$db->where([
    ['status', 'active'],
    ['idGrup', 5]
])->get('produk');

// OR WHERE
$db->where([['status', 'active']])
    ->orWhere([['status', 'pending']])
    ->get('produk');

// String-based WHERE
$db->where('harga > 10000')->get('produk');
```

### ORDER BY

```php
// ORDER BY nmProduk ASC, harga DESC
$db->order([
    'nmProduk' => 'ASC',
    'harga' => 'DESC'
])->get('produk');
```

Alternative single-column:

```php
$db->order('nmProduk', 'ASC')->get('produk');
```

### LIMIT & OFFSET

```php
// LIMIT 10 OFFSET 20
$db->limit(10)->offset(20)->get('produk');
```

### JOINs

```php
// INNER JOIN grup ON produk.idGrup = grup.idGrup
$db->select(['produk.*', 'grup.nmGrup'])
    ->join('grup', 'produk.idGrup = grup.idGrup')
    ->get('produk');

// LEFT JOIN
$db->leftJoin('grup', 'produk.idGrup = grup.idGrup')
    ->get('produk');

// Same as join()
$db->innerJoin('grup', 'produk.idGrup = grup.idGrup')
    ->get('produk');
```

### GROUP BY

```php
$db->select(['idGrup', 'COUNT(*) as total'])
    ->groupBy('idGrup')
    ->get('produk');
```

### Chaining Everything

```php
$result = $db->select(['produk.*', 'grup.nmGrup'])
    ->join('grup', 'produk.idGrup = grup.idGrup')
    ->where([['produk.status', 'active']])
    ->orWhere([['produk.status', 'pending']])
    ->order(['produk.nmProduk' => 'ASC'])
    ->limit(10)
    ->offset(0)
    ->get('produk')
    ->result();
```

## INSERT

```php
$success = $db->insert('produk', [
    'nmProduk' => 'Produk Baru',
    'harga' => 15000,
    'idGrup' => 3
]);

if ($success) {
    $newId = $db->lastId();
}
```

## UPDATE

```php
$db->where([['idProduk', '42']])
    ->update('produk', [
        'nmProduk' => 'Nama Baru',
        'harga' => 20000
    ]);
```

## DELETE

```php
$db->where([['idProduk', '42']])
    ->delete('produk');
```

## Transactions

```php
$db->startTransaction();

try {
    $db->insert('transaksi', ['total' => 50000]);
    $transaksiId = $db->lastId();
    
    $db->insert('transaksi_detail', [
        'idTransaksi' => $transaksiId,
        'idProduk' => 10,
        'qty' => 2
    ]);
    
    $db->commit();
} catch (\Exception $e) {
    $db->rollback();
    throw $e;
}
```

## Raw SQL

For complex queries, use `query()` directly:

```php
$result = $db->query('SELECT * FROM produk WHERE harga > 10000 ORDER BY nmProduk');
$rows = $result->result();
```

> **Warning**: Always use parameterized queries or sanitize input when using raw SQL to prevent SQL injection.

## Schema Operations (Migrations)

See [Migrations](migrations.md) for `create()`, `drop()`, `alter()`, `addColumn()`, `modifyColumn()`, `dropColumn()`, `rename()`, `truncate()`, `createIndex()`, `dropIndex()`.

## Best Practices

1. **Use the query builder** — avoid raw SQL when the builder can express it
2. **Always chain `where()` before `update()`/`delete()`** — prevents accidental mass updates
3. **Use transactions for multi-step writes** — ensures data integrity
4. **Select only needed columns** — `select(['id', 'name'])` not `get('table')`
5. **Check `lastId()` after insert** — verify the insert succeeded
