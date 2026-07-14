# Middleware

Middleware intercepts the request/response pipeline. It runs before the controller and can modify the request, authenticate users, add headers, or short-circuit with an early response.

## Creating Middleware

```php
<?php
namespace Selvi\Tests\Middlewares;

use Selvi\Exception;
use Selvi\Input\Request;

class AuthMiddleware {

    function validateToken(Request $request) {
        $token = $request->header('Authorization');
        if (!$token) {
            throw new Exception('Token tidak ditemukan', 'auth/token-missing', 401);
        }

        // Validate token...
        $token = str_replace('Bearer ', '', $token);
        // ... token validation logic ...

        return true;
    }
}
```

## How Middleware Works

The middleware chain wraps the controller action like an onion:

```
Request
  → Middleware 1 (before)
    → Middleware 2 (before)
      → Controller Action
    → Middleware 2 (after)
  → Middleware 1 (after)
Response
```

In Selvi, the middleware receives a `$next` callable parameter. Call it to proceed to the next layer:

```php
function myMiddleware($next) {
    // Before: pre-processing
    $response = $next();  // Execute the next layer
    // After: post-processing
    return $response;
}
```

## The `$next` Callable

Middleware can intercept calls using the `$next` parameter:

```php
class CorsMiddleware {
    function handle($next) {
        // Pre-processing
        header('Access-Control-Allow-Origin: *');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            return response('', 204);
        }

        // Execute the rest of the pipeline
        return $next();
    }
}
```

## Attaching Middleware to Routes

### Individual Route

```php
Route::get('/auth', 'Controllers\\AuthController@info')
    ->setMiddleware('Middleware\\AuthMiddleware@validateToken');
```

### Route Group

```php
Route::withMiddleware(
    ['Middleware\\AuthMiddleware@validateToken'],
    function () {
        Route::get('/produk', 'Controllers\\ProdukController@result');
        Route::post('/produk', 'Controllers\\ProdukController@insert');
    }
);
```

### Multiple Middleware

Middleware is applied in order — the first element runs outermost:

```php
Route::withMiddleware(
    [
        'Middleware\\CorsMiddleware@handle',    // Runs first (outermost)
        'Middleware\\AuthMiddleware@validateToken', // Runs second
        'Middleware\\LoggerMiddleware@log'       // Runs third (innermost)
    ],
    function () {
        // Protected routes
    }
);
```

## Middleware with Parameters

Middleware can pass values to the controller via the route's `params`:

```php
class AuthMiddleware {
    private mixed $penggunaAktif;

    function validateToken(Request $request) {
        // ... validate and decode token ...
        $this->penggunaAktif = $decodedUser;
    }

    function user() {
        return $this->penggunaAktif;
    }
}
```

Then access it in the controller via type-hinted injection:

```php
class AuthController {
    function info(AuthMiddleware $auth) {
        $user = $auth->user();
        return jsonResponse((array)$user);
    }
}
```

## Middleware Chain in Framework

The framework builds the middleware chain in `Framework::run()`:

```php
// Simplified middleware chain construction
$action = function () use ($route) {
    // Controller action
};

foreach ($route->getMiddleware() as $middleware) {
    $action = function () use ($middleware, $route, $action) {
        $params = $route->params();
        $params['next'] = $action;  // Pass next layer as $next param
        $ref = Injector::resolve($middleware, $params);
        return call_user_func($ref['cb'], ...$ref['params']);
    };
}

$action()->send();
```

## Best Practices

1. **Single responsibility** — each middleware does one thing (auth, CORS, logging, rate-limit)
2. **Fail fast** — throw exceptions or return early for invalid requests
3. **Use meaningful middleware names** — `AuthMiddleware`, `CorsMiddleware`, `ThrottleMiddleware`
4. **Keep middleware stateless** when possible
5. **Order middleware carefully** — CORS before auth, auth before business logic
