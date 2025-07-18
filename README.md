# Selvi Framework
⚡ Super fast PHP Framework for building API

## Quick Start

1. Get this framework via composer on your project directory (inside www folder if you are using Apache)

```
$ composer require mochrira/selvi-framework
```

2. Create `app/Controllers` folder inside your project directory
3. Create file `HomeController.php` inside `app/Controllers` with this content

```
<?php 

namespace App\Controllers;
use Selvi\Controller;

class HomeController extends Controller {

    function index() {
        return response('Welcome to Selvi Framework');
    }

}

```

4. Create index.php

```
<?php

require('vendor/autoload.php');
Selvi\Routing\Route::get('/', 'App\\Controllers\\HomeController@index');
Selvi\Framework::run();
```

5. Create `.htaccess` file

```
Options +FollowSymLinks
RewriteEngine On

RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [L]
```


6. Edit your composer.json

```
{
    ...
    "autoload": {
        "psr-4": {
            "App\\": "app/"
        }
    }
    ...
}
```

7. Run `composer update` to update your composer autoload
8. Done. Open `http://localhost/your-project` on your browser to test it

## Database Configuration

1. Buka http://localhost:1000, buat database baru dengan nama `example_app`
2. Buat file `app/Config/database.php` dengan konten sebagai berikut:

```
<?php

use Selvi\Database\Manager;
use Selvi\Database\Migration;
use Selvi\Cli;

Manager::add('main', [
    'driver' => 'mysql',
    'host' => 'mariadb.database',
    'username' => 'root',
    'password' => 'changeme',
    'database' => 'example_app'
]);
Migration::addMigrations('main', [ BASEPATH.'/app/Migrations' ]);
Cli::register('migrate', Migration::class);
```

3. Modifikasi file `index.php` dengan menambahkan baris berikut setelah require vendor
```
<?php

require __DIR__.'/vendor/autoload.php';
define('BASEPATH', __DIR__); // tambahkan baris ini
require __DIR__.'/app/Config/database.php'; // tambahkan baris ini

Selvi\Routing\Route::get('/', 'App\\Controllers\\HomeController@index');
\Selvi\Framework::run();
```

## Migration

1. Buat folder `app/Migrations`
2. Buat file `20250702_01_init.php` (format : YYYYMMDD_{index}_{konteks}.php), dengan konten sebagai berikut :

```
<?php

return function ($schema, $direction) {
    if($direction == 'up') :
        $schema->create('kontak', [
            'idKontak' => 'INT(11) PRIMARY KEY AUTO_INCREMENT',
            'nmKontak' => 'VARCHAR(150)',
            'nomor' => 'VARCHAR(50)'
        ]);
    endif;

    if($direction == 'down') :
        $schema->drop('kontak');
    endif;
};
```
3. Jalankan migrasi dengan perintah `php index.php migrate main up`

## Swagger UI

1. Buat file `www/specs/index.html` dengan konten sebagai berikut :

```
<!DOCTYPE html>
<html lang="en">
    <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="SwaggerUI" />
    <title>SwaggerUI</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@4.5.0/swagger-ui.css" />
    </head>

    <body>
        <div id="swagger-ui"></div>
        <script src="https://unpkg.com/swagger-ui-dist@4.5.0/swagger-ui-bundle.js" crossorigin></script>
        <script>
        window.onload = () => {
            window.ui = SwaggerUIBundle({
                url: './index.yaml',
                dom_id: '#swagger-ui',
                persistAuthorization: true
            });
        };
        </script>
    </body>
</html>
```

2. Buat file `www/specs/index.yaml` dengan konten sebagai berikut :

```
openapi: '3.0.2'
info:
  title: Kontak API
  version: '1.0'

servers:
  - url: http://localhost:8080
    description: Development

paths:
  /kontak:
    get:
      summary: Mengambil semua data kontak
      tags: [Kontak]
      parameters:
        - in: query
          name: offset
          schema:
            type: integer
        - in: query
          name: limit
          schema:
            type: integer
        - in: query
          name: search
          schema:
            type: string
      responses:
        '200':
          description: Semua object kontak dalam database
    
    post:
      summary: Menambahkan kontak baru
      tags: [Kontak]
      requestBody:
        required: true
        content:
          application/json:
            schema: 
              type: object
              properties:
                nmKontak:
                  type: string
                nomor:
                  type: string
              example:
                nmKontak: Moch. Rizal
                nomor: 82143255597
      responses:
        '201':
          description: Berhasil menambahkan kontak

  /kontak/{idKontak}:
    get:
      summary: Mengambil kontak berdasarkan ID
      tags: [Kontak]
      parameters:
        - in: path
          required: true
          name: idKontak
          schema: 
            type: integer
      responses:
        '200':
          description: Object kontak terpilih
    
    patch:
      summary: Mengubah kontak berdasarkan ID
      tags: [Kontak]
      parameters:
        - in: path
          required: true
          name: idKontak
          schema: 
            type: integer
      requestBody:
        required: true
        content:
          application/json:
            schema: 
              type: object
              properties:
                nmKontak:
                  type: string
                nomor:
                  type: string
              example:
                nmKontak: Moch. Rizal Rachmdani
                nomor: 82143255597
      responses:
        '204':
          description: Berhasil merubah kontak
    
    delete:
      summary: Menghapus kontak berdasarkan ID
      tags: [Kontak]
      parameters:
        - in: path
          required: true
          name: idKontak
          schema: 
            type: integer
      responses:
        '204' :
          description: Kontak berhasil dihapus
```
