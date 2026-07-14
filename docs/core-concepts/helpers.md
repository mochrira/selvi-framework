# Helpers

Selvi provides global helper functions for common tasks. These are automatically loaded via Composer autoload.

## Response Helpers

### `jsonResponse($data, $code, $options)`

Create a JSON response:

```php
return jsonResponse(['name' => 'Selvi'], 200);
return jsonResponse(null, 201);
return jsonResponse($items, 200, JSON_UNESCAPED_UNICODE);
```

### `response($content, $code)`

Create a plain text/HTML response:

```php
return response('OK', 200);
return response('<h1>Hello</h1>', 200);
```

### `view($file, $vars)`

Create a view response:

```php
return view('dashboard.php', ['title' => 'Home'])->render();
```

## URL Helpers

### `baseUrl()`

Get the base URL (scheme + host + subdirectory):

```php
$base = baseUrl();  // 'http://localhost/myapp'
```

### `currentUrl()`

Get the current full URL:

```php
$url = currentUrl();  // 'http://localhost/myapp/produk/42?page=1'
```

### `siteUrl($uri)`

Build a full URL from a path:

```php
$url = siteUrl('/produk/42');  // 'http://localhost/myapp/produk/42'
```

### `redirect($uri)`

Redirect to another URL:

```php
redirect('/login');
redirect('/produk/' . $newId);
```

## Dependency Injection Helper

### `inject($className)`

Resolve a class from the container:

```php
$request = inject(Request::class);
$db = inject(Selvi\Database\Schema::class);
```

This is equivalent to `Factory::resolve($className)`.

## Best Practices

1. **Use `jsonResponse()` for APIs** — it ensures consistent JSON output
2. **Use `siteUrl()` for links** — it handles subdirectory installations correctly
3. **Prefer injection over `inject()`** — use constructor injection in classes
4. **Don't overuse `redirect()`** — it terminates execution immediately
