<?php 

require 'vendor/autoload.php';

use App\Controllers\HomeController;
use Selvi\Database\Drivers\SQLServerDriver;
use Selvi\Factory;
use Selvi\Response;
use Selvi\Route;
use Selvi\Framework;
use Selvi\Uri;
use Selvi\Database\Manager;

/** Simplest way */
Route::get('/',function() { 
    return new Response('Index');
});

/** Function Name */
function functionName() {
    return new Response('Function Name');
}
Route::get('/function','functionName');

/** Class and Method */
Route::get('/home', '\App\Controllers\HomeController@index');

/** with URI Parameters, you can implement it to another style */
Route::get('/function/{name}', function (string $name) {
    return new Response(json_encode([
        'name' => $name
    ], JSON_PRETTY_PRINT));
});

/** with Dependency Injection, you can implement it to another style */
Route::get('/dependency/{name}', function (string $name, Uri $uri) {
    return new Response(json_encode([
        'name' => $name,
        'baseUrl' => $uri->baseUrl(),
        'currentUrl' => $uri->currentUrl(),
        'currentUri' => $uri->string(),
        'segments' => $uri->segments()
    ], JSON_PRETTY_PRINT));
});

Manager::add('main', [
    'host' => 'mariadb.database',
    'username' => 'root',
    'password' => 'RDF?jq8eec',
    'database' => 'test'
], 'mysql');

Route::get('/db', function () {
    $db = Manager::get('main');
    $db->connect();
    
    return new Response('Halo');
});

Framework::run();