# Exception Handling

Selvi provides a structured exception handling system with customizable handlers.

## Exception Classes

| Class | Code String | HTTP Code | Usage |
|---|---|---|---|
| `Selvi\Exception` | Custom | 500 (default) | Base application exception |
| `Selvi\Exception\HttpException` | `http/error` | 404 | Route not found |
| `Selvi\Exception\DatabaseException` | `database/error` | 500 | Database errors |

## Throwing Exceptions

```php
use Selvi\Exception;

// Basic exception with custom error code
throw new Exception('Periksa kembali isian anda', 'auth/invalid-input', 400, $errorData);

// With additional data
throw new Exception('Validation failed', 'validation/error', 422, [
    'fields' => ['email' => 'Invalid format']
]);
```

Constructor signature:

```php
Exception::__construct(
    string $message,       // Human-readable message
    string $codeString,    // Machine-readable error code
    int $error = 500,      // HTTP status code
    mixed $data = null     // Additional error data
)
```

## Adding Data to Exceptions

```php
throw (new Exception('Error', 'files/invalid-type', 500))
    ->with('fileName', $file['name'])
    ->with('fileType', $fileType);
```

Retrieve data with `get()`:

```php
$fileName = $e->get('fileName');
$allData = $e->getData();
```

## Setting Up Exception Handlers

Configure handlers in `app/Config/exception.php`:

```php
<?php
Selvi\Exception\Handler::setDefaultHandlers();
Selvi\Exception\Handler::listen();
```

### Default Handlers

| Handler | Handles Exception | JSON Response |
|---|---|---|
| `exception/framework` | `Selvi\Exception` | `{code, message, data?}` |
| `exception/database` | `Selvi\Exception\DatabaseException` | `{code, message, sql: {state, query}}` |
| `exception/http` | `Selvi\Exception\HttpException` | `{code, message, request: {uri, method}}` |
| `default` | `\Throwable` (any) | `{code, message, trace}` |

## Custom Exception Handlers

Register custom handlers for specific exception types:

```php
use Selvi\Exception\Handler;

// Handler for a specific exception
Handler::set('exception/validation', function (\App\Exception\ValidationException $e) {
    jsonResponse([
        'code' => $e->getCodeString(),
        'message' => $e->getMessage(),
        'errors' => $e->getData()
    ], 422)->send();
});

// Override default handler
Handler::set('default', function (\Throwable $e) {
    jsonResponse([
        'code' => 'internal/error',
        'message' => 'Terjadi kesalahan internal',
        // Don't expose trace in production
        'trace' => $_ENV['APP_DEBUG'] ? $e->getTraceAsString() : null
    ], 500)->send();
});
```

## Exception Response Format

All exceptions return a JSON response:

```json
{
    "code": "auth/invalid-input",
    "message": "Periksa kembali isian anda",
    "data": {
        "username": "Username harus diisi"
    }
}
```

## How Handlers Are Matched

Handlers are matched by the **parameter type** of the handler closure:

```php
Handler::set('custom', function (\App\Exception\MyException $e) {
    // This handler is matched when MyException is thrown
});
```

The framework uses reflection to check if the thrown exception type matches the handler's first parameter type.

## Best Practices

1. **Use semantic error codes** — `auth/invalid-input` is better than `ERR001`
2. **Don't expose internals** — in production, hide stack traces
3. **Include validation details** — return which fields failed and why
4. **Create domain exceptions** — extend `Selvi\Exception` for business-specific errors
5. **Throw early, catch late** — throw exceptions where the error occurs, handle at the framework level
