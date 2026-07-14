# Installation

## Prerequisites

Ensure your environment meets the following requirements:

| Requirement | Minimum Version |
|---|---|
| PHP | 8.0+ |
| Composer | 2.0+ |
| MySQL (optional) | 5.7+ |
| SQL Server (optional) | 2016+ |

Required PHP Extensions:
- `pdo` (and `pdo_mysql` / `pdo_sqlsrv` for database)
- `json`
- `mbstring`
- `fileinfo`

## Install via Composer

Create a new project using Composer:

```bash
composer require mochrira/selvi-framework
```

Or clone and install manually:

```bash
git clone <repository-url> my-project
cd my-project
composer install
```

## Web Server Configuration

### Apache

Create an `.htaccess` file in your project root:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

### Nginx

Add this to your server block:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### PHP Built-in Server (Development Only)

```bash
php -S localhost:8000
```

## Entry Point

Create an `index.php` as your application entry point:

```php
<?php
use Selvi\Env;

require 'vendor/autoload.php';

define('BASEPATH', __DIR__);

// Load environment variables
Env::load(BASEPATH . '/private/.ENV');

// Load configurations
require './app/Config/exception.php';
require './app/Config/database.php';
require './app/Config/routes.php';

// Run the framework
\Selvi\Framework::run();
```

## Environment File

Create a `.ENV` file in your `private/` directory:

```env
DB_DRIVER=mysql
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=my_database

KEY_ACCESS=your-access-token-secret
KEY_REFRESH=your-refresh-token-secret
```

> **Security Note**: Never commit the `.ENV` file to version control. Add it to `.gitignore`.

## Next Steps

- [Project Structure](project-structure.md) — Understand the directory layout
- [Quick Start](quick-start.md) — Build your first API endpoint
