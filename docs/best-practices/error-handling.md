# Error Handling Best Practices

## Use Semantic Error Codes

Adopt a consistent error code format: `category/specific-error`

```
auth/invalid-input
auth/token-expired
auth/token-missing
auth/forbidden
validation/missing-field
validation/invalid-format
database/connection-failed
database/duplicate-entry
files/invalid-type
files/file-too-large
files/upload-failed
http/not-found
http/method-not-allowed
```

## Structured Exception Throwing

```php
// Bad: generic exception, no context
throw new \Exception('Error');

// Good: semantic code, proper HTTP status, and data
throw new Exception(
    'Username atau password salah',
    'auth/invalid-credentials',
    401,
    ['attempts_remaining' => 3]
);
```

## Validation Error Pattern

```php
class AuthController {
    function getToken(Request $request) {
        $data = json_decode($request->raw(), true);
        
        $errors = [];
        if (empty($data['username'])) $errors['username'] = 'Username harus diisi';
        if (empty($data['password'])) $errors['password'] = 'Password harus diisi';
        
        if (count($errors) > 0) {
            throw new Exception(
                'Periksa kembali isian anda',
                'auth/invalid-input',
                400,
                $errors
            );
        }
        
        // Proceed with authentication...
    }
}
```

## Custom Exception Classes

For domain-specific errors, extend `Selvi\Exception`:

```php
<?php
namespace App\Exceptions;

use Selvi\Exception;

class InsufficientStockException extends Exception {
    function __construct(string $productName, int $requested, int $available) {
        parent::__construct(
            "Stok {$productName} tidak mencukupi",
            'product/insufficient-stock',
            422,
            [
                'product' => $productName,
                'requested' => $requested,
                'available' => $available
            ]
        );
    }
}
```

Then register a handler:

```php
Handler::set('product/stock', function (InsufficientStockException $e) {
    jsonResponse([
        'code' => $e->getCodeString(),
        'message' => $e->getMessage(),
        'data' => $e->getData()
    ], 422)->send();
});
```

## Database Error Handling

```php
try {
    $db->startTransaction();
    $db->insert('produk', $data);
    $db->commit();
} catch (DatabaseException $e) {
    $db->rollback();
    throw new Exception(
        'Gagal menyimpan produk',
        'product/save-failed',
        500,
        ['sql_state' => $e->getState()]
    );
}
```

## Production vs Development Errors

```php
// app/Config/exception.php
Handler::set('default', function (\Throwable $e) {
    $response = [
        'code' => 'internal/error',
        'message' => 'Terjadi kesalahan internal'
    ];
    
    // Only expose trace in development
    if (Env::get('APP_DEBUG', false)) {
        $response['trace'] = $e->getTraceAsString();
        $response['file'] = $e->getFile();
        $response['line'] = $e->getLine();
    }
    
    jsonResponse($response, 500)->send();
});
```

## Response Consistency

All error responses should follow this structure:

```json
{
    "code": "category/error-name",
    "message": "Human-readable message in user's language",
    "data": {
        // Optional: additional error context
    }
}
```

## Best Practices Summary

1. **Use semantic codes** — machine-readable, consistent format
2. **Throw early, catch late** — throw where the error occurs, handle at framework level
3. **Include context** — pass relevant data with exceptions
4. **Log errors server-side** — the client only needs `code` and `message`
5. **Never expose internals in production** — no stack traces, file paths, or SQL
6. **Use appropriate HTTP status codes** — 4xx for client errors, 5xx for server errors
7. **Be consistent** — all endpoints should return the same error format
