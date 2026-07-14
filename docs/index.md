# Selvi Framework Documentation

Selvi is a super fast, lightweight PHP framework designed specifically for building RESTful APIs. It follows the MVC (Model-View-Controller) architectural pattern with a clean, minimal API inspired by modern PHP frameworks.

## Key Features

- **Zero-config routing** with support for dynamic URI parameters and route groups
- **Powerful Dependency Injection** container with automatic resolution
- **Multi-driver database abstraction** (MySQL, SQL Server) with fluent query builder
- **Schema migrations & seeders** for database versioning
- **JWT authentication** support via `lcobucci/jwt`
- **PSR-4 autoloading** compliant
- **Symfony Console** integration for CLI commands
- **Exception handling** with customizable handlers
- **File upload** with validation
- **JSON & HTML response** helpers

---

## Table of Contents

### Getting Started
- [Installation](getting-started/installation.md)
- [Project Structure](getting-started/project-structure.md)
- [Quick Start](getting-started/quick-start.md)

### Core Concepts
- [Routing](core-concepts/routing.md)
- [Controllers](core-concepts/controllers.md)
- [Models](core-concepts/models.md)
- [Views](core-concepts/views.md)
- [Middleware](core-concepts/middleware.md)
- [Dependency Injection](core-concepts/dependency-injection.md)
- [Request Handling](core-concepts/request-handling.md)
- [Responses](core-concepts/responses.md)
- [Exception Handling](core-concepts/exception-handling.md)
- [Environment Configuration](core-concepts/environment.md)
- [Helpers](core-concepts/helpers.md)

### Database
- [Configuration](database/configuration.md)
- [Query Builder](database/query-builder.md)
- [Migrations](database/migrations.md)
- [Seeders](database/seeders.md)

### CLI
- [Console Commands](cli/commands.md)

### Best Practices
- [Project Structure](best-practices/structure.md)
- [Security](best-practices/security.md)
- [Error Handling](best-practices/error-handling.md)
- [Performance](best-practices/performance.md)

### Examples
- [Building a CRUD API](examples/crud-api.md)
- [Authentication with JWT](examples/jwt-auth.md)
- [File Upload API](examples/file-upload.md)

---

## Requirements

- PHP 8.0 or higher
- Composer
- MySQL 5.7+ or SQL Server (for database features)
- Apache/Nginx with `mod_rewrite` (for URL rewriting)

## License

MIT License
