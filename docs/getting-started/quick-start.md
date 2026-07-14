# Quick Start

This guide walks you through creating your first Selvi Framework API endpoint in 5 minutes.

## Step 1: Environment Setup

Create `private/.ENV`:

```env
DB_DRIVER=mysql
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=my_app
```

## Step 2: Entry Point

Create `index.php`:

```php
<?php
use Selvi\Env;

require 'vendor/autoload.php';
define('BASEPATH', __DIR__);

Env::load(BASEPATH . '/private/.ENV');
require './app/Config/exception.php';
require './app/Config/routes.php';

\Selvi\Framework::run();
```

## Step 3: Exception Handling

Create `app/Config/exception.php`:

```php
<?php
Selvi\Exception\Handler::setDefaultHandlers();
Selvi\Exception\Handler::listen();
```

## Step 4: Define a Route

Create `app/Config/routes.php`:

```php
<?php
use Selvi\Routing\Route;

Route::get('/hello', function () {
    return jsonResponse(['message' => 'Hello, World!']);
});

Route::get('/hello/{name}', function (string $name) {
    return jsonResponse(['message' => "Hello, {$name}!"]);
});
```

## Step 5: Test Your API

Start the development server:

```bash
php -S localhost:8000
```

Test with cURL:

```bash
# Simple hello
curl http://localhost:8000/hello

# Parameterized hello
curl http://localhost:8000/hello/Selvi
```

**Response:**
```json
{
    "message": "Hello, Selvi!"
}
```

## Step 6: Add a Controller

For better organization, move logic to a controller.

Create `app/Controllers/HelloController.php`:

```php
<?php
namespace Selvi\Tests\Controllers;

class HelloController {
    function greet(string $name = 'World') {
        return jsonResponse([
            'message' => "Hello, {$name}!",
            'timestamp' => date('c')
        ]);
    }
}
```

Update `app/Config/routes.php`:

```php
<?php
use Selvi\Routing\Route;

Route::get('/hello', 'Selvi\\Tests\\Controllers\\HelloController@greet');
Route::get('/hello/{name}', 'Selvi\\Tests\\Controllers\\HelloController@greet');
```

The `Controller@method` string syntax tells Selvi which class and method to invoke. Since `$name` in the route matches the `$name` parameter in the method signature, the framework auto-resolves it via dependency injection.

## What's Next?

- [Routing](../core-concepts/routing.md) — Learn about all routing capabilities
- [Controllers](../core-concepts/controllers.md) — Controller patterns and best practices
- [Responses](../core-concepts/responses.md) — JSON, HTML, and custom responses
