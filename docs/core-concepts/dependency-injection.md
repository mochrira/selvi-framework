# Dependency Injection

Selvi Framework uses automatic dependency injection (DI) to resolve class dependencies. There is **no manual wiring** — the container figures it out via reflection.

## How It Works

The DI system consists of two main classes:

| Component | Role |
|---|---|
| `Selvi\Factory` | Service container — creates and caches class instances |
| `Selvi\Injector` | Method resolver — resolves method parameters and dependencies |

### Factory (Service Container)

`Factory` manages singleton instances. When you request a class, it:

1. Checks if an instance already exists (singleton)
2. If not, uses reflection on the constructor
3. Resolves all constructor parameters recursively
4. Instantiates and caches the instance

```php
use Selvi\Factory;

// First call: creates and caches instance
$request = Factory::resolve(Request::class);

// Second call: returns cached instance (same object)
$sameRequest = Factory::resolve(Request::class);
```

> All classes resolved via `Factory` are **singletons** — only one instance exists per class.

### Injector (Method Resolver)

`Injector` resolves method parameters using reflection:

```php
use Selvi\Injector;

$ref = Injector::resolve('Controllers\\ProdukController@update', [
    'idProduk' => '42'  // Known parameters (from route)
]);

// $ref = ['cb' => [ProdukController, 'update'], 'params' => [Request, '42']]
call_user_func($ref['cb'], ...$ref['params']);
```

## Resolution Order

When resolving a method parameter, `Injector` follows this priority:

1. **Known parameters** — explicitly passed in `$knownParams` array (matched by name)
2. **Type-hinted classes** — auto-resolved via `Factory::resolve()`
3. **Default values** — if the parameter has a default
4. **Nullable** — if the parameter allows null, `null` is passed

## Constructor Injection (Recommended)

```php
class ProdukController {
    function __construct(
        private ProdukModel $ProdukModel,  // Auto-resolved
        private File $fileService           // Auto-resolved
    ) { }
}
```

This is the preferred pattern — declare dependencies in the constructor.

## Method Injection

```php
class AuthController {
    function getToken(Request $request, AuthMiddleware $auth) {
        // $request → auto-resolved from container
        // $auth    → auto-resolved from container
    }
}
```

## Route Parameter Injection

Route parameters are matched by **name**:

```php
// Route: /produk/{idProduk}
Route::get('/produk/{idProduk}', 'Controllers\\ProdukController@row');

// Controller method
function row(string $idProduk) {
    // $idProduk matches {idProduk} in route URI
}
```

The parameter name in the method signature **must match** the route parameter name exactly.

## The `inject()` Helper

Use `inject()` to manually resolve a class from anywhere:

```php
$request = inject(Request::class);
$db = inject(Selvi\Database\Schema::class);
```

This calls `Factory::resolve()` internally.

## Manual Factory Registration

You can manually set instances in the container:

```php
use Selvi\Factory;

// Manually set an instance
Factory::$instances[MyService::class] = new MyService($config);

// Now all resolutions return your custom instance
$service = Factory::resolve(MyService::class);
```

## Best Practices

1. **Constructor injection > method injection** — clearer dependencies
2. **Depend on abstractions** — type-hint interfaces rather than concrete classes
3. **Avoid `inject()` in models** — use constructor injection
4. **Don't abuse the container** — it's for services, not value objects
5. **Keep constructors simple** — too many dependencies signals a class doing too much
