# Models

Models encapsulate database access logic. Each model should represent a single database table or domain concept.

## Creating a Model

```php
<?php
namespace Selvi\Tests\Models;

use Selvi\Database\Manager;
use Selvi\Database\Schema;

class ProdukModel {

    private Schema $db;

    function __construct() {
        $this->db = Manager::get('main');
    }

    function row(array $where) {
        return $this->db->where($where)->get('produk')->row();
    }

    function result() {
        return $this->db->get('produk')->result();
    }

    function insert(array $data) {
        if ($this->db->insert('produk', $data) !== false) {
            return $this->db->lastId();
        }
        return false;
    }

    function update(array $where, array $data) {
        return $this->db->where($where)->update('produk', $data);
    }

    function delete(array $where) {
        return $this->db->where($where)->delete('produk');
    }
}
```

## Obtaining a Database Connection

Use `Manager::get('connectionName')` to retrieve a configured database schema:

```php
$this->db = Manager::get('main');
```

The connection name must match a key registered in `app/Config/database.php`.

## Query Patterns

### WHERE Conditions

The `where()` method accepts an array of conditions. Each condition is a `[$column, $value]` pair:

```php
// Single condition
$this->db->where([['idProduk', '123']])->get('produk')->row();

// Multiple conditions (AND)
$this->db->where([
    ['status', 'active'],
    ['idGrup', 5]
])->get('produk')->result();
```

### OR WHERE

```php
$this->db->where([['status', 'active']])
    ->orWhere([['status', 'pending']])
    ->get('produk')->result();
```

### SELECT Specific Columns

```php
$this->db->select(['idProduk', 'nmProduk', 'harga'])
    ->where([['idGrup', 5]])
    ->get('produk')->result();
```

### ORDER, LIMIT, OFFSET

```php
$this->db->order(['nmProduk' => 'ASC'])
    ->limit(10)
    ->offset(20)
    ->get('produk')->result();
```

### JOINs

```php
$this->db->select(['produk.*', 'grup.nmGrup'])
    ->join('grup', 'produk.idGrup = grup.idGrup')
    ->where([['produk.status', 'active']])
    ->get('produk')->result();
```

Available join types: `join()` (INNER), `innerJoin()`, `leftJoin()`.

### Aggregation

```php
function count($where = []) {
    return $this->db->select('COUNT(idProduk) as total')
        ->where($where)
        ->get('produk')->row()->total;
}
```

### GROUP BY

```php
$this->db->select(['idGrup', 'COUNT(idProduk) as total'])
    ->groupBy('idGrup')
    ->get('produk')->result();
```

## The Base Model Class

Selvi provides `Selvi\Model` (extends `Selvi\Base`). Extend it for shared model behavior:

```php
use Selvi\Model;

class ProdukModel extends Model {
    // Shared model functionality can be added to the base class
}
```

## Best Practices

1. **One model per table** — keeps responsibility clear
2. **Encapsulate all queries** — controllers should never touch `Manager` or `Schema` directly
3. **Return typed results** — use `row()` for single records, `result()` for collections
4. **Handle null results** — `row()` returns `null` when no record is found
5. **Use meaningful method names** — `findActive()` rather than generic `result(['status' => 'active'])`
6. **Validate data before insert/update** — either in the model or at the controller level
