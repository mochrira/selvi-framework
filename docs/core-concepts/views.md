# Views

Selvi provides a simple but flexible view system for rendering HTML templates.

## Rendering a View

Use the `view()` helper to create a View instance, then call `render()`:

```php
use Selvi\View;

Route::get('/dashboard', function () {
    return view('dashboard.php', [
        'title' => 'Dashboard',
        'user' => 'Admin'
    ])->render(200);
});
```

## Passing Data to Views

```php
// Via the view() helper
return view('produk/list.php', [
    'items' => $produkList,
    'title' => 'Daftar Produk'
])->render();

// Via setVar() method
$v = new View('produk/list.php');
$v->setVar('items', $produkList);
$v->setVar('title', 'Daftar Produk');
return $v->render();
```

## Template Files

Views are plain PHP files. Data is extracted into the local scope:

```php
<!-- views/produk/list.php -->
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($title) ?></title>
</head>
<body>
    <h1><?= htmlspecialchars($title) ?></h1>
    <table>
        <?php foreach ($items as $item): ?>
        <tr>
            <td><?= htmlspecialchars($item->nmProduk) ?></td>
            <td><?= htmlspecialchars($item->harga) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
```

> **Security**: Always use `htmlspecialchars()` when outputting user-generated content to prevent XSS attacks.

## Adding Custom View Paths

By default, Selvi looks for views in `src/views/`. Add your own paths:

```php
use Selvi\View;

View::addPath(__DIR__ . '/app/Views');
View::addPath(__DIR__ . '/app/Views/admin');
```

Views are resolved by searching paths in order — the **first matching** file wins.

## Alternative: Direct Response

For APIs, you usually don't need views. Use JSON responses instead:

```php
Route::get('/api/produk', function () {
    $produk = $db->get('produk')->result();
    return jsonResponse($produk);
});
```

## Rendering Without Returning

Use `include()` to render a view inline (useful in layouts):

```php
$header = new View('partials/header.php');
$header->include();  // Outputs directly
```

## Best Practices

1. **Keep logic out of views** — views should only contain presentation (HTML + echo)
2. **Use partials** — break reusable UI pieces into separate files
3. **Escape output** — always escape with `htmlspecialchars()`
4. **Prefer API responses** — for SPA backends, use `jsonResponse()` instead of views
