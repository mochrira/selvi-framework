# Controllers

Controllers handle incoming HTTP requests, process them, and return responses. In Selvi, controllers should be **thin** — delegate business logic to models and services.

## Creating a Controller

```php
<?php
namespace Selvi\Tests\Controllers;

use Selvi\Tests\Models\ProdukModel;
use Selvi\Input\Request;

class ProdukController {

    function __construct(
        private ProdukModel $ProdukModel
    ) { }

    function result() {
        $result = $this->ProdukModel->result();
        return jsonResponse($result, 200);
    }

    function row(string $idProduk) {
        $result = $this->ProdukModel->row([['produk.idProduk', $idProduk]]);
        return jsonResponse($result, 200);
    }

    function insert(Request $request) {
        $data = json_decode($request->raw(), true);
        $idProduk = $this->ProdukModel->insert($data);
        return jsonResponse(['idProduk' => $idProduk], 201);
    }

    function update(Request $request, string $idProduk) {
        $data = json_decode($request->raw(), true);
        $this->ProdukModel->update([['produk.idProduk', $idProduk]], $data);
        return jsonResponse(null, 200);
    }

    function delete(string $idProduk) {
        $this->ProdukModel->delete([['produk.idProduk', $idProduk]]);
        return jsonResponse(null, 200);
    }
}
```

## Constructor Injection

Dependencies are automatically injected via the constructor. The framework resolves them from the IoC container:

```php
class ProdukController {
    function __construct(
        private ProdukModel $ProdukModel,
        private File $fileService   // Additional dependency
    ) { }
}
```

No manual wiring needed — Selvi's `Injector` and `Factory` handle it.

## Method Parameter Injection

Controller method parameters are resolved automatically:

| Parameter Source | Example |
|---|---|
| Route parameter | `string $idProduk` — from `{idProduk}` in URI |
| Type-hinted class | `Request $request` — auto-resolved from container |
| Middleware-injected | `AuthMiddleware $auth` — injected by middleware `params` |

```php
function update(Request $request, string $idProduk, AuthMiddleware $auth) {
    // $request  → auto-resolved container instance
    // $idProduk → from route /produk/{idProduk}
    // $auth     → injected by middleware
}
```

## The Base Controller Class

Selvi provides a `Selvi\Controller` base class that extends `Selvi\Base`:

```php
use Selvi\Controller;

class MyController extends Controller {
    // Base class provides shared functionality
}
```

> Currently `Base` and `Controller` are empty marker classes. Extend them for future compatibility and to add shared behavior across controllers.

## Controller Method Return Values

Controller methods must return a `Response` object. Use helper functions:

```php
// JSON response
return jsonResponse(['data' => $items], 200);

// HTML response
return view('template.php', ['title' => 'Home'])->render(200);

// Raw response
return response('OK', 200);

// Or return a Response object directly
return new JsonResponse(['error' => 'Not found'], 404);
```

## Best Practices

1. **Keep controllers thin** — no business logic, no raw SQL
2. **One controller per resource** — e.g., `ProdukController` handles `/produk` routes
3. **Use HTTP status codes correctly** — `200` for success, `201` for created, `400` for bad request, `404` for not found
4. **Validate input early** — return validation errors before processing
5. **Always return a Response** — never `echo` or `print` directly
6. **Use dependency injection** — never use `new` inside controller methods
