# Migrations

Migrations provide version control for your database schema. They allow you to define changes in PHP files and apply them consistently across environments.

## Migration File Naming

Files in `app/Migrations/` follow this convention:

```
YYYYMMDD_NN_description.php
```

Examples:
- `20240505_01_init.php` — Initial schema
- `20240506_02_addTransaksi.php` — Add transaksi tables
- `20240510_01_renameItem.php` — Rename item column

Files are sorted alphabetically by filename, which ensures chronological execution.

## Creating a Migration

```php
<?php
// app/Migrations/20240505_01_init.php

use Selvi\Database\Schema;

return function (Schema $schema, string $direction) {
    if ($direction === 'up') {
        $schema->create('produk', [
            'idProduk' => 'INT(11) PRIMARY KEY AUTO_INCREMENT',
            'nmProduk' => 'VARCHAR(150) NOT NULL',
            'harga' => 'DECIMAL(15,2) DEFAULT 0',
            'idGrup' => 'INT(11)',
            'status' => "ENUM('active','inactive') DEFAULT 'active'"
        ]);

        $schema->create('grup', [
            'idGrup' => 'INT(11) PRIMARY KEY AUTO_INCREMENT',
            'nmGrup' => 'VARCHAR(50) NOT NULL'
        ]);
    }

    if ($direction === 'down') {
        $schema->drop('produk');
        $schema->drop('grup');
    }
};
```

## Migration Methods

### `create(string $table, array $columns)`

```php
$schema->create('users', [
    'id' => 'INT(11) PRIMARY KEY AUTO_INCREMENT',
    'name' => 'VARCHAR(100) NOT NULL',
    'email' => 'VARCHAR(100) UNIQUE',
    'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
]);
```

### `drop(string $table)`

```php
$schema->drop('old_table');
```

### `alter(string $table)`

Modify table structure with column operations:

```php
$schema->addColumn('phone', 'VARCHAR(20)')->alter('users');
$schema->modifyColumn('email', 'VARCHAR(200) NOT NULL')->alter('users');
$schema->dropColumn('old_field')->alter('users');
$schema->addColumnAfter('name', 'middle_name', 'VARCHAR(50)')->alter('users');
```

### `rename(string $from, string $to)`

```php
$schema->rename('produk', 'products');
```

### `truncate(string $table)`

```php
$schema->truncate('temp_logs');
```

### Indexes

```php
$schema->createIndex('produk', 'idx_produk_grup', ['idGrup']);
$schema->dropIndex('produk', 'idx_produk_grup');
```

### Primary Keys

```php
$schema->addPrimary('id', 'pk_users')->alter('users');
$schema->dropPrimary()->alter('users');
```

## Registering Migration Paths

In `app/Config/database.php`:

```php
use Selvi\Database\Migration;

Migration::add('main', BASEPATH . '/app/Migrations');
```

## Running Migrations

### CLI Command

```bash
php console.php migrate main up
php console.php migrate main down
```

### Options

| Option | Description |
|---|---|
| `--step, -s` | Number of migration files to run |
| `--all, -a` | Run all pending migrations |

```bash
# Run next 2 migrations
php console.php migrate main up --step=2

# Run all pending migrations
php console.php migrate main up --all

# Rollback last migration
php console.php migrate main down --step=1
```

## Migration Tracking

Selvi tracks migrations in a `_migration` table:

| Column | Description |
|---|---|
| `filename` | Migration file name |
| `direction` | `up` or `down` |
| `start` | Unix timestamp when started |
| `finish` | Unix timestamp when finished |
| `output` | `success` or `failed` |
| `error_msg` | Error message if failed |
| `error_state` | SQLSTATE if failed |
| `error_query` | Failing SQL query |

Already-run migrations are **skipped** on subsequent runs.

## Programmatic Migration

Run migrations from code:

```php
use Selvi\Database\Migration;

$migration = new Migration();
$migration->up('main', step: -1, logger: function ($msg, $status, $type) {
    echo "[{$type}] {$msg}\n";
});
```

## Best Practices

1. **Always provide both `up` and `down`** — ensures reversibility
2. **One concern per migration** — don't create 5 tables in one file
3. **Use descriptive names** — `add_email_to_users` not `update_table1`
4. **Never modify existing migrations** — create new ones for changes
5. **Test migrations on a copy** — verify before running on production
6. **Include indexes** — add indexes in migration files, not manually
