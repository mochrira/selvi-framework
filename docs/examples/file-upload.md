# File Upload API

This tutorial implements a secure file upload endpoint with type validation and size limits.

## Overview

```
POST /file    → Upload a file (requires auth)
```

## Step 1: File Library

Selvi includes `Selvi\Libraries\File` for file upload handling:

```php
use Selvi\Libraries\File;

$file = new File();
$result = $file->upload('attachment', [
    'allowedTypes' => ['image/jpeg', 'image/png', 'application/pdf'],
    'maxSize' => 5 * 1024 * 1024,  // 5 MB
    'path' => BASEPATH . '/storage/uploads',
    'name' => 'custom-name'
]);
```

### Upload Options

| Option | Type | Description |
|---|---|---|
| `allowedTypes` | `string[]` | Allowed MIME types (empty = allow all) |
| `maxSize` | `int` | Maximum file size in bytes |
| `path` | `string` | Target directory (auto-created if missing) |
| `name` | `string` | Custom filename (without extension) |

### Upload Result

```php
[
    'fileName'  => 'original-name.jpg',      // Original filename
    'rawName'   => 'original-name',          // Name without extension
    'fileExt'   => 'jpg',                    // File extension
    'fileType'  => 'image/jpeg',             // MIME type
    'filePath'  => 'uploads/photo.jpg',      // Relative path
    'fullPath'  => '/full/path/photo.jpg',   // Absolute path
    'fileSize'  => 2048576                   // Size in bytes
]
```

## Step 2: File Controller

Create `app/Controllers/FileController.php`:

```php
<?php
namespace App\Controllers;

use Selvi\Exception;
use Selvi\Input\Request;
use Selvi\Libraries\File;

class FileController {

    function __construct(
        private File $fileService
    ) { }

    /**
     * POST /file — Upload a file
     */
    function upload(Request $request) {
        try {
            $result = $this->fileService->upload('file', [
                'allowedTypes' => [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp',
                    'application/pdf'
                ],
                'maxSize' => 10 * 1024 * 1024,  // 10 MB
                'path' => BASEPATH . '/storage/uploads/' . date('Y/m'),
                'name' => uniqid('file_')  // Prevent name collisions
            ]);

            return jsonResponse([
                'fileName' => $result['fileName'],
                'filePath' => $result['filePath'],
                'fileType' => $result['fileType'],
                'fileSize' => $result['fileSize']
            ], 201);

        } catch (Exception $e) {
            // File library already throws structured exceptions
            throw $e;
        }
    }
}
```

## Step 3: Route

```php
use Selvi\Routing\Route;

Route::post('/file', 'App\\Controllers\\FileController@upload')
    ->setMiddleware('App\\Middlewares\\AuthMiddleware@validateToken');
```

## Step 4: Error Handling

The File library throws exceptions with specific error codes:

| Error Code | HTTP Code | Condition |
|---|---|---|
| `files/invalid-type` | 500 | MIME type not in allowed list |
| `files/file-too-large` | 500 | File exceeds max size |
| `files/unknown-error` | 500 | `move_uploaded_file` failed |
| `files/failed-to-upload` | 500 | General upload failure |

The default exception handler automatically converts these to JSON responses.

## Step 5: Testing

```bash
# Upload a file
curl -X POST http://localhost:8000/file \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@/path/to/photo.jpg"

# Response (201):
# {
#   "fileName": "photo.jpg",
#   "filePath": "storage/uploads/2024/05/file_663a1b2c3d4e5.jpg",
#   "fileType": "image/jpeg",
#   "fileSize": 2048576
# }

# Upload with wrong type (500):
# {
#   "code": "files/invalid-type",
#   "message": "Type not allowed"
# }

# Upload too large (500):
# {
#   "code": "files/file-too-large",
#   "message": "File too large"
# }
```

## Step 6: Advanced — Store File Metadata in Database

Create a migration for file tracking:

```php
<?php
// app/Migrations/20240510_01_files.php
return function (Schema $schema, string $direction) {
    if ($direction === 'up') {
        $schema->create('files', [
            'idFile' => 'INT(11) PRIMARY KEY AUTO_INCREMENT',
            'fileName' => 'VARCHAR(255)',
            'filePath' => 'VARCHAR(500)',
            'fileType' => 'VARCHAR(100)',
            'fileSize' => 'BIGINT',
            'uploadedBy' => 'INT(11)',
            'createdAt' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
        ]);
    }

    if ($direction === 'down') {
        $schema->drop('files');
    }
};
```

Updated controller with DB tracking:

```php
class FileController {

    function __construct(
        private File $fileService,
        private FileModel $FileModel,
        private AuthMiddleware $auth
    ) { }

    function upload(Request $request) {
        $result = $this->fileService->upload('file', [
            'allowedTypes' => ['image/jpeg', 'image/png'],
            'maxSize' => 10 * 1024 * 1024,
            'path' => BASEPATH . '/storage/uploads/' . date('Y/m'),
            'name' => uniqid('file_')
        ]);

        // Store metadata
        $idFile = $this->FileModel->insert([
            'fileName' => $result['fileName'],
            'filePath' => $result['filePath'],
            'fileType' => $result['fileType'],
            'fileSize' => $result['fileSize'],
            'uploadedBy' => $this->auth->user()->idPengguna
        ]);

        return jsonResponse([
            'idFile' => $idFile,
            'fileName' => $result['fileName'],
            'filePath' => $result['filePath']
        ], 201);
    }
}
```

## Security Best Practices

1. **Validate MIME type** — use `allowedTypes` to restrict to known types
2. **Limit file size** — use `maxSize` to prevent DoS
3. **Rename files** — use `uniqid()` to prevent path traversal and collisions
4. **Store outside web root** — or protect upload directory with `.htaccess`
5. **Authenticate uploads** — always protect upload endpoints with middleware
6. **Scan for malware** — integrate ClamAV or similar in production
