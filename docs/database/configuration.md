# Database Configuration

Selvi supports MySQL and SQL Server via a unified database abstraction layer.

## Supported Drivers

| Driver | Class |
|---|---|
| MySQL | `Selvi\Database\Drivers\MySQL\MySQLSchema` |
| SQL Server | `Selvi\Database\Drivers\SQLSrv\SQLSrvSchema` |

## Adding a Database Connection

Configure connections in `app/Config/database.php`:

```php
<?php
use Selvi\Database\Manager;
use Selvi\Env;

Manager::add('main', [
    'driver' => Env::get('DB_DRIVER', 'mysql'),
    'host' => Env::get('DB_HOST', 'localhost'),
    'username' => Env::get('DB_USER', 'root'),
    'password' => Env::get('DB_PASS', ''),
    'database' => Env::get('DB_NAME', 'my_app')
]);
```

## Multiple Database Connections

Register multiple named connections:

```php
Manager::add('main', [ /* MySQL config */ ]);
Manager::add('logs', [ /* SQL Server config */ ]);
Manager::add('analytics', [ /* Another MySQL config */ ]);
```

Access them by name:

```php
$mainDb = Manager::get('main');
$logsDb = Manager::get('logs');
```

## Environment Variables

Typical `.ENV` configuration:

```env
# Primary database
DB_DRIVER=mysql
DB_HOST=localhost
DB_USER=root
DB_PASS=secret
DB_NAME=my_app

# Log database
LOG_DB_DRIVER=sqlsrv
LOG_DB_HOST=192.168.1.100
LOG_DB_USER=sa
LOG_DB_PASS=secret
LOG_DB_NAME=app_logs
```

## Connection Lifecycle

- Connections are created lazily — the first query triggers connection
- Each `Manager::get('name')` returns the same `Schema` instance
- Connections remain open for the duration of the request

## Error Handling

Database errors throw `Selvi\Exception\DatabaseException`:

```php
try {
    $db->insert('produk', $data);
} catch (DatabaseException $e) {
    // $e->getMessage()  — Human-readable error
    // $e->getSql()      — The SQL that caused the error
    // $e->getState()    — SQLSTATE error code
}
```

The default exception handler formats this as:

```json
{
    "code": "database/error",
    "message": "Duplicate entry '123' for key 'PRIMARY'",
    "sql": {
        "state": "23000",
        "query": "INSERT INTO produk ..."
    }
}
```

## Best Practices

1. **Never hardcode credentials** — always use `Env::get()`
2. **Use descriptive connection names** — `main`, `logs`, `analytics`
3. **Separate read/write connections** — if you have replication, register `main_read` and `main_write`
4. **Handle database exceptions** — let the framework handle them, but log for debugging
