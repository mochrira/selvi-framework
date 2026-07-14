# Seeders

Seeders populate your database with initial or test data. They use the same Schema API as migrations.

## Seeder File Naming

Files in `app/Seeders/` follow the same convention as migrations:

```
YYYYMMDD_NN_description.php
```

## Creating a Seeder

```php
<?php
// app/Seeders/20260104_01_pengguna.php

use Selvi\Database\Schema;

return function (Schema $schema) {
    $schema->insert('pengguna', [
        'nmPengguna' => 'Administrator',
        'username' => 'admin',
        'password' => md5('admin')
    ]);

    $schema->insert('pengguna', [
        'nmPengguna' => 'User Biasa',
        'username' => 'user',
        'password' => md5('user123')
    ]);
};
```

## Seeder with Relationships

```php
<?php
return function (Schema $schema) {
    // Insert parent record
    $schema->insert('grup', ['nmGrup' => 'Elektronik']);
    $grupId = $schema->lastId();

    // Insert child records
    $schema->insert('produk', [
        'nmProduk' => 'Laptop',
        'harga' => 15000000,
        'idGrup' => $grupId
    ]);

    $schema->insert('produk', [
        'nmProduk' => 'Smartphone',
        'harga' => 5000000,
        'idGrup' => $grupId
    ]);
};
```

## Registering Seeder Paths

In `app/Config/database.php`:

```php
use Selvi\Database\Seeder;

Seeder::add('main', BASEPATH . '/app/Seeders');
```

## Running Seeders

```bash
# Run all seeders
php console.php seeder main

# Run specific number of seeder files
php console.php seeder main --step=1
```

## Seeder Tracking

Like migrations, seeders are tracked in the `_migration` table with `direction = 'seed'`. Already-run seeders are skipped.

## Data Generation with Realistic Data

For production-like testing data, use loops and random values:

```php
<?php
return function (Schema $schema) {
    $grups = ['Elektronik', 'Pakaian', 'Makanan', 'Minuman', 'ATK'];

    foreach ($grups as $nm) {
        $schema->insert('grup', ['nmGrup' => $nm]);
        $grupId = $schema->lastId();

        for ($i = 1; $i <= 10; $i++) {
            $schema->insert('produk', [
                'nmProduk' => "{$nm} Item {$i}",
                'harga' => rand(1000, 100000),
                'idGrup' => $grupId
            ]);
        }
    }
};
```

## Best Practices

1. **Keep seeders idempotent** — they should be safe to run multiple times
2. **Use realistic data** — production-like data catches edge cases
3. **Don't seed sensitive data** — no real passwords, API keys, or PII
4. **Seed in order** — parent tables before child tables (respect foreign keys)
5. **Separate dev and test seeds** — use different paths for different environments
6. **Use `md5()` or `password_hash()`** — always hash passwords in seed data
