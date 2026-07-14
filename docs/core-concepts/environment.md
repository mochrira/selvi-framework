# Environment Configuration

Selvi uses a `.ENV` file to store environment-specific configuration. The `Selvi\Env` class handles loading and accessing these variables.

## The .ENV File

Create `private/.ENV`:

```env
# Database
DB_DRIVER=mysql
DB_HOST=localhost
DB_USER=root
DB_PASS=secret
DB_NAME=my_database

# JWT Keys
KEY_ACCESS=your-256-bit-access-secret
KEY_REFRESH=your-256-bit-refresh-secret

# Application
APP_DEBUG=true
APP_URL=http://localhost:8000
```

### ENV File Format

- `KEY=VALUE` format, one per line
- Lines starting with `#` are comments
- Values can be quoted: `KEY="value"` or `KEY='value'`
- Quoted values have quotes stripped automatically

## Loading ENV Variables

Load the file once at the start of your application:

```php
use Selvi\Env;

Env::load(BASEPATH . '/private/.ENV');
```

The `load()` method:
- Only loads the file **once** (idempotent)
- Throws `\RuntimeException` if the file is not found or not readable
- Populates `$_ENV`, `$_SERVER`, and `getenv()`

## Accessing Variables

```php
// Preferred: using Env::get() with default fallback
$dbHost = Env::get('DB_HOST', 'localhost');
$debug = Env::get('APP_DEBUG', false);

// Also available via standard PHP
$dbHost = $_ENV['DB_HOST'];
$dbHost = $_SERVER['DB_HOST'];
$dbHost = getenv('DB_HOST');
```

## Using ENV in Config Files

A typical `app/Config/database.php`:

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

## Environment-Specific Configuration

For multi-environment setups, use different `.ENV` files:

```
private/
├── .ENV              # Default / development
├── .ENV.production   # Production settings
└── .ENV.testing      # Test environment
```

Load based on environment:

```php
$env = getenv('APP_ENV') ?: 'development';
$envFile = $env === 'production' ? '.ENV.production' : '.ENV';
Env::load(BASEPATH . '/private/' . $envFile);
```

## Best Practices

1. **Never commit `.ENV`** — add it to `.gitignore`
2. **Provide `.ENV.example`** — with placeholder values for documentation
3. **Use defaults** — `Env::get('KEY', 'default')` makes the app work even without the file
4. **Keep secrets in ENV** — API keys, passwords, JWT secrets
5. **Use descriptive key names** — `DB_HOST` not `H`, `MAIL_DRIVER` not `MD`
6. **Validate required keys** — check critical config exists at startup
