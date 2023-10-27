<?php 

// define('BASEPATH', __DIR__);
// require 'vendor/autoload.php';
// Selvi\Route::get('/', 'HomeController@index', function ($next, $args) {
//     return $next();
// });
// Selvi\Framework::run();

require 'vendor/autoload.php';

class HomeController extends Selvi\Controller {

    function index() {
        echo 'Hello World';
    }
    
}

Selvi\Uri::setBaseUrl('http://localhost:8091/test');
Selvi\Route::bind('/', 'HomeController@index');
Selvi\Framework::run();