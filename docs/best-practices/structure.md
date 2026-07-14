# Project Structure Best Practices

## Recommended Directory Layout

```
my-api/
├── private/                        # NEVER web-accessible
│   ├── .ENV                        # Environment variables
│   └── .ENV.example                # Template for env vars
│
├── app/
│   ├── Config/                     # Bootstrap configuration
│   │   ├── database.php
│   │   ├── exception.php
│   │   └── routes.php
│   │
│   ├── Controllers/                # HTTP layer — thin, delegates to services
│   │   ├── AuthController.php
│   │   └── ProdukController.php
│   │
│   ├── Models/                     # Database access layer
│   │   ├── ProdukModel.php
│   │   └── PenggunaModel.php
│   │
│   ├── Services/                   # Business logic (optional but recommended)
│   │   ├── ProdukService.php       # Complex business rules
│   │   └── NotificationService.php # Cross-cutting concerns
│   │
│   ├── Middlewares/                 # Request pipeline interceptors
│   │   ├── AuthMiddleware.php
│   │   └── CorsMiddleware.php
│   │
│   ├── Validators/                 # Input validation (optional)
│   │   └── ProdukValidator.php
│   │
│   ├── Migrations/                 # Database schema versions
│   │   └── YYYYMMDD_NN_desc.php
│   │
│   └── Seeders/                    # Database seed data
│       └── YYYYMMDD_NN_desc.php
│
├── public/                         # Web root (recommended)
│   ├── index.php                   # Entry point
│   └── .htaccess                   # URL rewriting
│
├── storage/                        # Writable files (optional)
│   ├── logs/
│   └── uploads/
│
├── console.php                     # CLI entry point
├── composer.json
└── README.md
```

## Public Directory (Recommended for Production)

Serve only `public/` as the web root:

```
DocumentRoot /var/www/my-api/public
```

Move `index.php` and `.htaccess` to `public/` and adjust paths:

```php
// public/index.php
require __DIR__ . '/../vendor/autoload.php';
define('BASEPATH', __DIR__);
Env::load(BASEPATH . '/../private/.ENV');
```

## Separation of Concerns

| Layer | Responsibility | Must NOT |
|---|---|---|
| **Controller** | Handle HTTP, validate input, call services, return response | Contain business logic or SQL |
| **Service** | Business logic, orchestration, transactions | Handle HTTP concerns |
| **Model** | Database queries, data access | Contain business rules |
| **Middleware** | Auth, CORS, logging, rate limiting | Contain business logic |

### Anti-pattern: Fat Controller

```php
// BAD: Controller contains business logic and SQL
class ProdukController {
    function insert(Request $request) {
        $data = json_decode($request->raw(), true);
        if ($data['harga'] < 1000) throw new Exception('...');
        $db = Manager::get('main');
        $db->insert('produk', $data);
        // ... more inline SQL and logic
    }
}
```

### Correct: Thin Controller

```php
// GOOD: Controller delegates to model/service
class ProdukController {
    function __construct(private ProdukService $service) {}
    
    function insert(Request $request) {
        $data = json_decode($request->raw(), true);
        $id = $this->service->create($data);
        return jsonResponse(['id' => $id], 201);
    }
}
```

## Configuration Files

Keep config files focused:

- `routes.php` — Only route definitions
- `database.php` — Only database connections and migration/seeder paths
- `exception.php` — Only exception handler registration

Don't mix concerns — routes should not contain database config.

## Autoloading

Update `composer.json` to autoload your `app/` namespace:

```json
{
    "autoload": {
        "psr-4": {
            "Selvi\\": "src/",
            "App\\": "app/"
        }
    }
}
```

Then: `composer dump-autoload`
