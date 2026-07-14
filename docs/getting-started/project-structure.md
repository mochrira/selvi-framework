# Project Structure

A typical Selvi Framework project follows this structure:

```
my-project/
├── composer.json              # Project dependencies & autoload config
├── index.php                  # Web entry point
├── console.php                # CLI entry point
├── .htaccess                  # Apache URL rewriting
│
├── private/                   # Sensitive files (not web-accessible)
│   └── .ENV                   # Environment variables
│
├── app/                       # Application code
│   ├── Config/                # Configuration files
│   │   ├── database.php       # Database connection config
│   │   ├── exception.php      # Exception handler setup
│   │   └── routes.php         # Route definitions
│   │
│   ├── Controllers/           # Controller classes
│   │   ├── AuthController.php
│   │   └── ProdukController.php
│   │
│   ├── Models/                # Model classes
│   │   ├── ProdukModel.php
│   │   └── PenggunaModel.php
│   │
│   ├── Middlewares/           # Middleware classes
│   │   └── AuthMiddleware.php
│   │
│   ├── Migrations/            # Database migration files
│   │   └── 20240505_01_init.php
│   │
│   └── Seeders/               # Database seeder files
│       └── 20260104_01_pengguna.php
│
├── vendor/                    # Composer dependencies
└── docs/                      # Project documentation
```

## Directory Roles

| Directory | Purpose |
|---|---|
| `private/` | Stores sensitive config that must never be served publicly |
| `app/Config/` | Framework boot configuration (routes, database, exceptions) |
| `app/Controllers/` | HTTP request handlers — **thin layer**, delegates to models |
| `app/Models/` | Database access layer — encapsulate all DB queries here |
| `app/Middlewares/` | Request/response pipeline interceptors (auth, logging, CORS) |
| `app/Migrations/` | Database schema versioning — named `YYYYMMDD_NN_desc.php` |
| `app/Seeders/` | Database seed data — named `YYYYMMDD_NN_desc.php` |

## Naming Conventions

| Component | Convention | Example |
|---|---|---|
| Controllers | `{Name}Controller` | `ProdukController` |
| Models | `{Name}Model` | `ProdukModel` |
| Middlewares | `{Name}Middleware` | `AuthMiddleware` |
| Migrations | `YYYYMMDD_NN_description.php` | `20240505_01_init.php` |
| Seeders | `YYYYMMDD_NN_description.php` | `20260104_01_pengguna.php` |

> **Best Practice**: Keep `private/` outside the web root whenever possible. If not possible, protect it with `.htaccess` deny rules.
