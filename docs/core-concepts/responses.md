# Responses

Selvi provides flexible response types for both API and web applications.

## Response Classes

| Class | Purpose |
|---|---|
| `Selvi\Output\Response` | Plain text/HTML response |
| `Selvi\Output\JsonResponse` | JSON-encoded response |

## JSON Responses (APIs)

Use the `jsonResponse()` helper for API endpoints:

```php
// Success response
return jsonResponse(['data' => $items], 200);

// Created response
return jsonResponse(['idProduk' => $newId], 201);

// Empty success
return jsonResponse(null, 200);

// Error response
return jsonResponse([
    'code' => 'validation/invalid-input',
    'message' => 'Nama produk harus diisi'
], 400);
```

The JSON is automatically encoded with `JSON_PRETTY_PRINT` by default.

### Custom JSON Options

```php
use Selvi\Output\JsonResponse;

$resp = new JsonResponse($data, 200, JSON_UNESCAPED_UNICODE);
$resp->send();
```

## HTML/Text Responses

Use `response()` for plain text or HTML:

```php
return response('<h1>Hello World</h1>', 200);
return response('OK', 200);
```

## View Responses

Use `view()` for HTML templates:

```php
return view('produk/list.php', [
    'items' => $produkList,
    'title' => 'Daftar Produk'
])->render(200);
```

See [Views](views.md) for more details.

## Redirects

Use the `redirect()` helper:

```php
redirect('/login');
redirect('/produk/' . $newId);
```

This sends a `Location` header and terminates execution.

## Setting Cookies

Cookies can be set on any Response object before sending:

```php
$response = jsonResponse(['token' => $jwt]);
$response->cookie('refresh', $refreshToken, time() + 86400, '/', '', false, true);
return $response;
```

Parameters: `cookie($name, $value, $expire, $path, $domain, $secure, $httponly)`.

## Response Lifecycle

1. Controller returns a `Response` object
2. The `send()` method is called
3. HTTP status code is set via `http_response_code()`
4. Content is echoed
5. Execution terminates

```php
// Manual response sending
$resp = new Response('Hello', 200);
$resp->send();  // Outputs "Hello" with 200 status and terminates
```

## HTTP Status Code Reference

| Code | Constant | Usage |
|---|---|---|
| 200 | OK | Successful GET, PATCH, DELETE |
| 201 | Created | Successful POST |
| 204 | No Content | Successful request with no body |
| 400 | Bad Request | Invalid input, validation errors |
| 401 | Unauthorized | Missing or invalid authentication |
| 403 | Forbidden | Authenticated but not authorized |
| 404 | Not Found | Resource not found |
| 405 | Method Not Allowed | Wrong HTTP method |
| 422 | Unprocessable Entity | Semantic validation errors |
| 500 | Internal Server Error | Unexpected server errors |

## Best Practices

1. **Use appropriate status codes** — don't return `200` for everything
2. **Structure error responses consistently** — always include `code` and `message`
3. **Return `null` for empty 200** — `jsonResponse(null, 200)` is cleaner than `jsonResponse(['success' => true])`
4. **Set cookies before `send()`** — cookies are headers, must come before output
5. **JSON for APIs, HTML for pages** — choose the right response type
