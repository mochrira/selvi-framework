# Routing

Routing maps HTTP requests to handler callbacks or controller methods. Selvi uses a declarative, fluent routing API.

## Basic Routes

Define routes in `app/Config/routes.php` using `Selvi\Routing\Route`:

```php
use Selvi\Routing\Route;

// GET request
Route::get('/produk', 'Selvi\\Tests\\Controllers\\ProdukController@result');

// POST request
Route::post('/produk', 'Selvi\\Tests\\Controllers\\ProdukController@insert');

// PATCH request
Route::patch('/produk/{id}', 'Selvi\\Tests\\Controllers\\ProdukController@update');

// DELETE request
Route::delete('/produk/{id}', 'Selvi\\Tests\\Controllers\\ProdukController@delete');

// OPTIONS request (CORS preflight)
Route::options('/produk', 'Selvi\\Tests\\Controllers\\ProdukController@options');
```

| HTTP Method | Route Method |
|---|---|
| `GET` | `Route::get()` |
| `POST` | `Route::post()` |
| `PATCH` | `Route::patch()` |
| `DELETE` | `Route::delete()` |
| `OPTIONS` | `Route::options()` |

## Route Callback Formats

Selvi supports three callback formats:

### 1. Controller String Syntax (Recommended)

```php
Route::get('/produk', 'Namespace\\ControllerClass@methodName');
```

The framework automatically instantiates the controller class and calls the specified method.

### 2. Closure / Callable

```php
Route::get('/status', function () {
    return jsonResponse(['status' => 'ok']);
});
```

Use closures for simple endpoints or quick prototypes.

### 3. Array Syntax

```php
Route::get('/produk', [ProdukController::class, 'result']);
```

## Dynamic Route Parameters

Wrap parameter names in curly braces `{}`. Parameters are automatically injected by name into your controller method:

```php
// Route definition
Route::get('/produk/{idProduk}', 'Controllers\\ProdukController@row');

// Controller method — parameter name MUST match route parameter
class ProdukController {
    function row(string $idProduk) {
        // $idProduk is automatically populated from the URL
        $produk = $this->ProdukModel->row([['idProduk', $idProduk]]);
        return jsonResponse($produk);
    }
}
```

> **Important**: The route parameter name and the controller method parameter name must match exactly. The framework uses reflection to match them by name.

## Route Groups

Group routes that share common middleware or prefixes:

```php
use Selvi\Routing\Route;

Route::withMiddleware(
    ['Middleware\\AuthMiddleware@validateToken'],
    function () {
        // All routes inside this group require authentication
        Route::get('/produk', 'Controllers\\ProdukController@result');
        Route::post('/produk', 'Controllers\\ProdukController@insert');
        Route::patch('/produk/{id}', 'Controllers\\ProdukController@update');
        Route::delete('/produk/{id}', 'Controllers\\ProdukController@delete');
    }
);
```

Nested groups are supported — middleware accumulates from outer to inner.

## Middleware on Individual Routes

```php
Route::get('/auth', 'Controllers\\AuthController@info')
    ->setMiddleware('Middleware\\AuthMiddleware@validateToken');

Route::patch('/auth', 'Controllers\\AuthController@refreshToken')
    ->setMiddleware('Middleware\\AuthMiddleware@validateRefreshToken');
```

## How Routing Works

1. `Framework::run()` resolves the current HTTP method and URI
2. `Router::resolve()` iterates through all registered routes
3. Each route's `match()` method checks:
   - HTTP method matches
   - URI pattern matches (with dynamic parameter extraction)
4. Middleware chain wraps the final handler
5. The resolved handler is invoked via dependency injection

## Route Matching Rules

- Routes are evaluated in the order they are registered
- The **first matching** route wins
- Static routes take no precedence over dynamic ones — order matters
- Register more specific routes before generic ones

```php
// CORRECT: Specific before generic
Route::get('/produk/export', 'Controllers\\ProdukController@export');
Route::get('/produk/{id}', 'Controllers\\ProdukController@row');

// WRONG: Generic will match /produk/export first
Route::get('/produk/{id}', 'Controllers\\ProdukController@row');
Route::get('/produk/export', 'Controllers\\ProdukController@export');
// "/produk/export" will never be reached!
```
