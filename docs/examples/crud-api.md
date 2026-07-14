# Building a Complete CRUD API

This tutorial walks through building a full CRUD API for a "Produk" (Product) resource.

## Overview

```
GET    /produk          → List all products
GET    /produk/{id}     → Get one product
POST   /produk          → Create a product
PATCH  /produk/{id}     → Update a product
DELETE /produk/{id}     → Delete a product
```

## Step 1: Database Migration

Create `app/Migrations/20240505_01_init_produk.php`:

```php
<?php
use Selvi\Database\Schema;

return function (Schema $schema, string $direction) {
    if ($direction === 'up') {
        $schema->create('grup', [
            'idGrup' => 'INT(11) PRIMARY KEY AUTO_INCREMENT',
            'nmGrup' => 'VARCHAR(50) NOT NULL'
        ]);

        $schema->create('produk', [
            'idProduk' => 'INT(11) PRIMARY KEY AUTO_INCREMENT',
            'nmProduk' => 'VARCHAR(150) NOT NULL',
            'harga' => 'DECIMAL(15,2) DEFAULT 0',
            'idGrup' => 'INT(11)',
            'status' => "ENUM('active','inactive') DEFAULT 'active'"
        ]);
    }

    if ($direction === 'down') {
        $schema->drop('produk');
        $schema->drop('grup');
    }
};
```

Run migration:

```bash
php console.php migrate main up
```

## Step 2: Model

Create `app/Models/ProdukModel.php`:

```php
<?php
namespace App\Models;

use Selvi\Database\Manager;
use Selvi\Database\Schema;

class ProdukModel {

    private Schema $db;

    function __construct() {
        $this->db = Manager::get('main');
    }

    function result(array $where = [], int $limit = 10, int $offset = 0) {
        return $this->db
            ->select(['produk.*', 'grup.nmGrup'])
            ->join('grup', 'produk.idGrup = grup.idGrup')
            ->where($where)
            ->order(['produk.nmProduk' => 'ASC'])
            ->limit($limit)
            ->offset($offset)
            ->get('produk')
            ->result();
    }

    function row(array $where) {
        return $this->db
            ->select(['produk.*', 'grup.nmGrup'])
            ->join('grup', 'produk.idGrup = grup.idGrup')
            ->where($where)
            ->get('produk')
            ->row();
    }

    function insert(array $data): int|false {
        if ($this->db->insert('produk', $data)) {
            return $this->db->lastId();
        }
        return false;
    }

    function update(array $where, array $data): bool {
        return $this->db->where($where)->update('produk', $data) !== false;
    }

    function delete(array $where): bool {
        return $this->db->where($where)->delete('produk') !== false;
    }
}
```

## Step 3: Controller

Create `app/Controllers/ProdukController.php`:

```php
<?php
namespace App\Controllers;

use App\Models\ProdukModel;
use Selvi\Exception;
use Selvi\Input\Request;

class ProdukController {

    function __construct(
        private ProdukModel $ProdukModel
    ) { }

    function result(Request $request) {
        $page = (int) ($request->get('page') ?? 1);
        $limit = (int) ($request->get('limit') ?? 10);
        $offset = ($page - 1) * $limit;

        $result = $this->ProdukModel->result([], $limit, $offset);
        return jsonResponse($result, 200);
    }

    function row(string $idProduk) {
        $produk = $this->ProdukModel->row([['produk.idProduk', $idProduk]]);

        if (!$produk) {
            throw new Exception(
                'Produk tidak ditemukan',
                'produk/not-found',
                404
            );
        }

        return jsonResponse($produk, 200);
    }

    function insert(Request $request) {
        $data = json_decode($request->raw(), true);

        // Validation
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

        // Sanitize input — only allowed fields
        $allowed = ['nmProduk', 'harga', 'idGrup', 'status'];
        $data = array_intersect_key($data, array_flip($allowed));

        $idProduk = $this->ProdukModel->insert($data);

        if ($idProduk === false) {
            throw new Exception('Gagal menyimpan', 'produk/save-failed', 500);
        }

        return jsonResponse(['idProduk' => $idProduk], 201);
    }

    function update(Request $request, string $idProduk) {
        // Check existence
        $existing = $this->ProdukModel->row([['produk.idProduk', $idProduk]]);
        if (!$existing) {
            throw new Exception('Produk tidak ditemukan', 'produk/not-found', 404);
        }

        $data = json_decode($request->raw(), true);
        $allowed = ['nmProduk', 'harga', 'idGrup', 'status'];
        $data = array_intersect_key($data, array_flip($allowed));

        $this->ProdukModel->update([['idProduk', $idProduk]], $data);
        return jsonResponse(null, 200);
    }

    function delete(string $idProduk) {
        $existing = $this->ProdukModel->row([['produk.idProduk', $idProduk]]);
        if (!$existing) {
            throw new Exception('Produk tidak ditemukan', 'produk/not-found', 404);
        }

        $this->ProdukModel->delete([['idProduk', $idProduk]]);
        return jsonResponse(null, 200);
    }
}
```

## Step 4: Routes

In `app/Config/routes.php`:

```php
<?php
use Selvi\Routing\Route;

Route::get('/produk', 'App\\Controllers\\ProdukController@result');
Route::get('/produk/{idProduk}', 'App\\Controllers\\ProdukController@row');
Route::post('/produk', 'App\\Controllers\\ProdukController@insert');
Route::patch('/produk/{idProduk}', 'App\\Controllers\\ProdukController@update');
Route::delete('/produk/{idProduk}', 'App\\Controllers\\ProdukController@delete');
```

## Step 5: Add Authentication (Optional)

Protect routes with middleware:

```php
Route::withMiddleware(
    ['App\\Middlewares\\AuthMiddleware@validateToken'],
    function () {
        Route::get('/produk', 'App\\Controllers\\ProdukController@result');
        Route::get('/produk/{idProduk}', 'App\\Controllers\\ProdukController@row');
        Route::post('/produk', 'App\\Controllers\\ProdukController@insert');
        Route::patch('/produk/{idProduk}', 'App\\Controllers\\ProdukController@update');
        Route::delete('/produk/{idProduk}', 'App\\Controllers\\ProdukController@delete');
    }
);
```

## Testing the API

```bash
# List products
curl http://localhost:8000/produk

# Get single product
curl http://localhost:8000/produk/1

# Create product
curl -X POST http://localhost:8000/produk \
  -H "Content-Type: application/json" \
  -d '{"nmProduk":"Laptop","harga":15000000,"idGrup":1}'

# Update product
curl -X PATCH http://localhost:8000/produk/1 \
  -H "Content-Type: application/json" \
  -d '{"harga":14000000}'

# Delete product
curl -X DELETE http://localhost:8000/produk/1
```

## Key Takeaways

1. **Migration defines schema** — version-controlled, repeatable
2. **Model encapsulates queries** — controllers never touch `Manager`
3. **Controller validates input** — returns 400 with specific errors
4. **Routes map HTTP to controllers** — clean, declarative
5. **Middleware protects routes** — auth is orthogonal to business logic
