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
2. Modifikasi file `index.php` dengan menambahkan baris berikut setelah require vendor
```
<?php

require __DIR__.'/vendor/autoload.php';
define(BASEPATH, __DIR__); // tambahkan baris ini
....
```
3. Buat folder `app/Config/database.php`, dengan konten sebagai berikut:

```
<?php

use Selvi\Database\Manager;
use Selvi\Database\Migration;

Manager::add('main', [
    'host' => 'maria.database',
    'username' => 'root',
    'password' => 'changeme',
    'database' => 'example_app'
]);
Migration::addMigrations('main', [ BASEPATH.'/app/Migrations' ]);
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
}
```
3. Jalankan migrasi dengan perintah `php index.php migrate main up`
