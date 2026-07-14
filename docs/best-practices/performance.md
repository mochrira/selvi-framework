# Performance Best Practices

## Database Optimization

### Select Only Needed Columns

```php
// BAD: SELECT * — fetches all columns
$db->get('produk')->result();

// GOOD: Select only what you need
$db->select(['idProduk', 'nmProduk', 'harga'])
    ->get('produk')->result();
```

### Use Indexes

```php
// In migrations, add indexes for frequently queried columns
$schema->create('produk', [
    'idProduk' => 'INT(11) PRIMARY KEY AUTO_INCREMENT',
    'nmProduk' => 'VARCHAR(150)',
    'idGrup' => 'INT(11)',
    'status' => "ENUM('active','inactive')"
]);

$schema->createIndex('produk', 'idx_produk_grup', ['idGrup']);
$schema->createIndex('produk', 'idx_produk_status', ['status']);
```

### Pagination

Never return all records at once:

```php
// BAD: Returns everything
function result() {
    return $this->db->get('produk')->result();
}

// GOOD: Paginated
function result(int $limit = 10, int $offset = 0, array $where = []) {
    return $this->db->where($where)
        ->limit($limit)
        ->offset($offset)
        ->get('produk')->result();
}
```

### Use COUNT for Total Only

```php
function count(array $where = []) {
    return $this->db->select('COUNT(*) as total')
        ->where($where)
        ->get('produk')->row()->total;
}
```

## Response Optimization

### Minimal JSON Responses

```php
// BAD: Unnecessary nesting
return jsonResponse(['success' => true, 'data' => ['items' => $rows]]);

// GOOD: Flat, minimal structure
return jsonResponse($rows);
```

### Appropriate Status Codes — No Body When Not Needed

```php
// 204 No Content for successful deletes
function delete(string $id) {
    $this->model->delete([['id', $id]]);
    return response('', 204);
}
```

## Caching (Planned Strategy)

While Selvi doesn't have built-in caching, you can implement it:

```php
class ProdukModel {
    function result() {
        $cacheFile = BASEPATH . '/storage/cache/produk_list.json';
        
        if (file_exists($cacheFile) && time() - filemtime($cacheFile) < 300) {
            return json_decode(file_get_contents($cacheFile));
        }
        
        $result = $this->db->get('produk')->result();
        file_put_contents($cacheFile, json_encode($result));
        return $result;
    }
}
```

## Autoloading

### Optimize Composer Autoload

```bash
# Production: use optimized autoloader
composer dump-autoload --optimize --no-dev
```

## Singleton Pattern (Built-in)

Selvi's `Factory` already caches instances. Don't create unnecessary objects:

```php
// Factory resolves once, reuses cached instance
$db1 = Factory::resolve(Request::class);  // Created
$db2 = Factory::resolve(Request::class);  // Cached — same instance
```

## PHP Configuration

### Production php.ini

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
realpath_cache_size=4096k
realpath_cache_ttl=600
```

## Query Efficiency Checklist

- [ ] Select specific columns, not `SELECT *`
- [ ] Add indexes on WHERE, JOIN, ORDER BY columns
- [ ] Use LIMIT for list endpoints
- [ ] Count with `SELECT COUNT(*)` not `count(result())`
- [ ] Use transactions for multi-table writes
- [ ] Avoid N+1 queries — join tables when possible
- [ ] Close connections explicitly if doing long-running tasks

## Response Size Checklist

- [ ] Return only necessary fields
- [ ] Paginate large collections
- [ ] Use 204 for no-content responses
- [ ] Compress responses (enable gzip in web server)
- [ ] Set proper Cache-Control headers for static data
