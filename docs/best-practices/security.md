# Security Best Practices

## Environment & Configuration

### Never Expose .ENV

```gitignore
# .gitignore
private/.ENV
private/.ENV.*
!.ENV.example
```

### Validate Environment at Startup

```php
// app/Config/database.php
$required = ['DB_HOST', 'DB_NAME', 'DB_USER'];
foreach ($required as $key) {
    if (!Env::get($key)) {
        throw new \RuntimeException("Missing required env var: {$key}");
    }
}
```

### Use Strong Secrets

```env
# GOOD: 256-bit random key
KEY_ACCESS=a8f5f167f44f4964e6c998dee8b4e8f5f167f44f4964e6c998dee8b4e8f5f167

# BAD: Guessable
KEY_ACCESS=secret123
```

Generate: `php -r "echo bin2hex(random_bytes(32));"`

## Input Validation

### Validate Early, Validate Strictly

```php
function insert(Request $request) {
    $data = json_decode($request->raw(), true);
    
    $errors = [];
    if (empty($data['nmProduk'])) {
        $errors['nmProduk'] = 'Nama produk harus diisi';
    }
    if (!isset($data['harga']) || $data['harga'] < 0) {
        $errors['harga'] = 'Harga tidak valid';
    }
    
    if (count($errors) > 0) {
        throw new Exception('Validasi gagal', 'validation/error', 400, $errors);
    }
    
    // Only process validated data
    $this->model->insert($data);
}
```

### Whitelist Input Fields

```php
// Only allow known fields
$allowed = ['nmProduk', 'harga', 'idGrup', 'status'];
$data = array_intersect_key($data, array_flip($allowed));
```

## Authentication & Authorization

### JWT Best Practices

```php
class AuthMiddleware {
    function validateToken(Request $request) {
        $header = $request->header('Authorization');
        if (!$header || !str_starts_with($header, 'Bearer ')) {
            throw new Exception('Token tidak valid', 'auth/invalid-token', 401);
        }
        
        $token = substr($header, 7);
        
        try {
            // Validate signature, expiration, issuer
            $parsed = $this->config->parser()->parse($token);
            
            $constraints = [
                new SignedWith($this->config->signer(), $this->config->signingKey()),
                new LooseValidAt(SystemClock::fromUTC()),
            ];
            $this->config->validator()->assert($parsed, ...$constraints);
            
        } catch (\Exception $e) {
            throw new Exception('Token tidak valid', 'auth/invalid-token', 401);
        }
    }
}
```

### Short Token Lifetimes

```php
// Access token: 15 minutes
$builder->expiresAt($now->modify('+15 minutes'));

// Refresh token: 7 days
$refreshBuilder->expiresAt($now->modify('+7 days'));
```

### Use HTTPS in Production

Set secure cookie flags:

```php
$response->cookie('refresh', $token, 
    time() + 604800,  // 7 days
    '/',              // path
    '',               // domain
    true,             // secure (HTTPS only)
    true              // httpOnly (no JS access)
);
```

## SQL Injection Prevention

### Use Query Builder (Automatic Protection)

```php
// SAFE: Uses parameterized queries
$db->where([['idProduk', $userInput]])->get('produk');

// SAFE: insert/update also parameterized
$db->insert('produk', $userData);
```

### NEVER Concatenate User Input

```php
// DANGEROUS: SQL injection risk
$db->query("SELECT * FROM produk WHERE nmProduk = '{$userInput}'");
```

## File Upload Security

```php
use Selvi\Libraries\File;

$file = new File();
$result = $file->upload('attachment', [
    'allowedTypes' => ['image/jpeg', 'image/png', 'application/pdf'],
    'maxSize' => 5 * 1024 * 1024,  // 5MB
    'path' => BASEPATH . '/storage/uploads',
    'name' => uniqid()  // Rename to prevent path traversal
]);
```

## General Security Checklist

- [ ] `.ENV` is in `.gitignore`
- [ ] All secrets use strong random values
- [ ] Input is validated before processing
- [ ] Passwords are hashed (`password_hash()`, not `md5()`)
- [ ] JWT tokens have short expiration
- [ ] Cookies use `secure` + `httpOnly` in production
- [ ] File uploads validate type, size, and extension
- [ ] Error responses don't leak stack traces in production
- [ ] HTTPS enforced in production
- [ ] CORS is configured explicitly (not `*` in production)
- [ ] Queries use builder (parameterized) — no string concatenation
