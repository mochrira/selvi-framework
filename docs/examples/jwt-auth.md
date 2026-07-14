# JWT Authentication API

This tutorial implements token-based authentication using `lcobucci/jwt` with access and refresh tokens.

## Prerequisites

Install JWT dependencies:

```bash
composer require lcobucci/jwt lcobucci/clock
```

## Overview

```
POST  /auth        → Login (get token)
GET   /auth        → Get current user info (requires token)
PATCH /auth        → Refresh token (requires refresh token)
```

## Step 1: User Migration

Create `app/Migrations/20240629_01_pengguna.php`:

```php
<?php
use Selvi\Database\Schema;

return function (Schema $schema, string $direction) {
    if ($direction === 'up') {
        $schema->create('pengguna', [
            'idPengguna' => 'INT(11) PRIMARY KEY AUTO_INCREMENT',
            'nmPengguna' => 'VARCHAR(100) NOT NULL',
            'username' => 'VARCHAR(50) UNIQUE NOT NULL',
            'password' => 'VARCHAR(255) NOT NULL'
        ]);
    }

    if ($direction === 'down') {
        $schema->drop('pengguna');
    }
};
```

## Step 2: Environment Configuration

Add to `private/.ENV`:

```env
KEY_ACCESS=your-256-bit-access-token-secret-key-here
KEY_REFRESH=your-256-bit-refresh-token-secret-key-here
```

Generate strong keys:

```bash
php -r "echo bin2hex(random_bytes(32));"
```

## Step 3: User Model

Create `app/Models/PenggunaModel.php`:

```php
<?php
namespace App\Models;

use Selvi\Database\Manager;
use Selvi\Database\Schema;

class PenggunaModel {

    private Schema $db;

    function __construct() {
        $this->db = Manager::get('main');
    }

    function row(array $where) {
        return $this->db->where($where)->get('pengguna')->row();
    }

    function insert(array $data): int|false {
        if ($this->db->insert('pengguna', $data)) {
            return $this->db->lastId();
        }
        return false;
    }
}
```

## Step 4: Auth Middleware

Create `app/Middlewares/AuthMiddleware.php`:

```php
<?php
namespace App\Middlewares;

use Selvi\Exception;
use Selvi\Input\Request;
use Selvi\Env;
use App\Models\PenggunaModel;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;

class AuthMiddleware {

    private Configuration $tokenConfig;
    private Configuration $refreshConfig;
    private mixed $currentUser = null;

    function __construct(
        private PenggunaModel $PenggunaModel
    ) {
        $this->tokenConfig = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText(Env::get('KEY_ACCESS'))
        );

        $this->refreshConfig = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText(Env::get('KEY_REFRESH'))
        );
    }

    /**
     * Get the currently authenticated user
     */
    function user() {
        return $this->currentUser;
    }

    /**
     * Generate access token (short-lived: 15 minutes)
     */
    function generateToken(array $claims = []): string {
        $now = new \DateTimeImmutable();
        $builder = $this->tokenConfig->builder()
            ->issuedAt($now)
            ->expiresAt($now->modify('+15 minutes'));

        foreach ($claims as $key => $value) {
            $builder = $builder->withClaim($key, $value);
        }

        return $builder
            ->getToken($this->tokenConfig->signer(), $this->tokenConfig->signingKey())
            ->toString();
    }

    /**
     * Generate refresh token (long-lived: 7 days)
     */
    function generateRefreshToken(array $claims = []): string {
        $now = new \DateTimeImmutable();
        $builder = $this->refreshConfig->builder()
            ->issuedAt($now)
            ->expiresAt($now->modify('+7 days'));

        foreach ($claims as $key => $value) {
            $builder = $builder->withClaim($key, $value);
        }

        return $builder
            ->getToken($this->refreshConfig->signer(), $this->refreshConfig->signingKey())
            ->toString();
    }

    /**
     * Validate access token from Authorization header
     */
    function validateToken(Request $request): bool {
        $header = $request->header('Authorization');

        if (!$header || !str_starts_with($header, 'Bearer ')) {
            throw new Exception(
                'Token tidak ditemukan',
                'auth/token-missing',
                401
            );
        }

        $token = substr($header, 7);

        try {
            $parsed = $this->tokenConfig->parser()->parse($token);

            $constraints = [
                new SignedWith($this->tokenConfig->signer(), $this->tokenConfig->signingKey()),
                new LooseValidAt(SystemClock::fromUTC()),
            ];
            $this->tokenConfig->validator()->assert($parsed, ...$constraints);

            // Load user from token claims
            $idPengguna = $parsed->claims()->get('idPengguna');
            $this->currentUser = $this->PenggunaModel->row([['idPengguna', $idPengguna]]);

            if (!$this->currentUser) {
                throw new Exception('Pengguna tidak ditemukan', 'auth/user-not-found', 401);
            }

            return true;
        } catch (Exception $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new Exception('Token tidak valid', 'auth/invalid-token', 401);
        }
    }

    /**
     * Validate refresh token from cookie
     */
    function validateRefreshToken(Request $request): bool {
        $refreshToken = $request->cookie('refresh');

        if (!$refreshToken) {
            throw new Exception(
                'Refresh token tidak ditemukan',
                'auth/refresh-missing',
                401
            );
        }

        try {
            $parsed = $this->refreshConfig->parser()->parse($refreshToken);

            $constraints = [
                new SignedWith($this->refreshConfig->signer(), $this->refreshConfig->signingKey()),
                new LooseValidAt(SystemClock::fromUTC()),
            ];
            $this->refreshConfig->validator()->assert($parsed, ...$constraints);

            $idPengguna = $parsed->claims()->get('idPengguna');
            $this->currentUser = $this->PenggunaModel->row([['idPengguna', $idPengguna]]);

            return true;
        } catch (\Exception $e) {
            throw new Exception('Refresh token tidak valid', 'auth/invalid-refresh', 401);
        }
    }
}
```

## Step 5: Auth Controller

Create `app/Controllers/AuthController.php`:

```php
<?php
namespace App\Controllers;

use App\Middlewares\AuthMiddleware;
use App\Models\PenggunaModel;
use Selvi\Exception;
use Selvi\Input\Request;

class AuthController {

    function __construct(
        private PenggunaModel $PenggunaModel
    ) { }

    /**
     * POST /auth — Login
     */
    function getToken(Request $request, AuthMiddleware $auth) {
        $data = json_decode($request->raw(), true);

        // Validate input
        $errors = [];
        if (empty($data['username'])) $errors['username'] = 'Username harus diisi';
        if (empty($data['password'])) $errors['password'] = 'Password harus diisi';

        $pengguna = null;
        if (empty($errors)) {
            $pengguna = $this->PenggunaModel->row([['username', $data['username']]]);

            if (!$pengguna) {
                $errors['username'] = 'Username tidak ditemukan';
            } elseif (!password_verify($data['password'], $pengguna->password)) {
                $errors['password'] = 'Password salah';
            }
        }

        if (count($errors) > 0) {
            throw new Exception(
                'Periksa kembali isian anda',
                'auth/invalid-input',
                400,
                $errors
            );
        }

        // Generate tokens
        $claims = ['idPengguna' => $pengguna->idPengguna];
        $accessToken = $auth->generateToken($claims);
        $refreshToken = $auth->generateRefreshToken($claims);

        // Return access token in body, refresh token in httpOnly cookie
        $response = jsonResponse(['token' => $accessToken]);

        $response->cookie(
            'refresh',
            $refreshToken,
            time() + 604800,  // 7 days
            '/',
            '',
            true,   // secure
            true    // httpOnly
        );

        return $response;
    }

    /**
     * GET /auth — Current user info
     */
    function info(AuthMiddleware $auth) {
        $user = (array) $auth->user();
        unset($user['password']);  // Never expose password
        return jsonResponse($user);
    }

    /**
     * PATCH /auth — Refresh token
     */
    function refreshToken(AuthMiddleware $auth) {
        $user = $auth->user();

        $token = $auth->generateToken([
            'idPengguna' => $user->idPengguna
        ]);

        return jsonResponse(['token' => $token]);
    }
}
```

## Step 6: Routes

In `app/Config/routes.php`:

```php
<?php
use Selvi\Routing\Route;

// Public routes
Route::post('/auth', 'App\\Controllers\\AuthController@getToken');

// Protected routes
Route::get('/auth', 'App\\Controllers\\AuthController@info')
    ->setMiddleware('App\\Middlewares\\AuthMiddleware@validateToken');

Route::patch('/auth', 'App\\Controllers\\AuthController@refreshToken')
    ->setMiddleware('App\\Middlewares\\AuthMiddleware@validateRefreshToken');
```

## Testing

```bash
# Login
curl -X POST http://localhost:8000/auth \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'

# Response: { "token": "eyJhbGciOi..." }
# Cookie: refresh=eyJhbGciOi...; HttpOnly; Secure

# Get user info (with token)
curl http://localhost:8000/auth \
  -H "Authorization: Bearer eyJhbGciOi..."

# Refresh token (sends cookie automatically)
curl -X PATCH http://localhost:8000/auth \
  -H "Cookie: refresh=eyJhbGciOi..."
```

## Token Flow

```mermaid
sequenceDiagram
    participant Client
    participant /auth (POST)
    participant /auth (GET)
    participant /produk

    Client->>/auth (POST): Login (username, password)
    /auth (POST)-->>Client: Access Token + Refresh Cookie

    Client->>/produk: Request with Bearer Token
    /produk-->>Client: Data (200)

    Note over Client: Token expires (15 min)

    Client->>/auth (PATCH): Refresh (cookie)
    /auth (PATCH)-->>Client: New Access Token

    Client->>/produk: Request with New Token
    /produk-->>Client: Data (200)
```

## Security Notes

- Access tokens expire in **15 minutes** — short-lived for security
- Refresh tokens expire in **7 days** — stored in httpOnly, Secure cookies
- Passwords are hashed with `password_hash()` — never store plain text
- The `password` field is always stripped before returning user data
- All token validation failures return `401` with semantic error codes
