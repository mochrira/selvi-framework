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
