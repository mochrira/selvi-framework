<?php 

require 'vendor/autoload.php';

use App\Controllers\HomeController;
use Selvi\Factory;
use Selvi\Response;
use Selvi\Route;
use Selvi\Framework;
use Selvi\Uri;

// Route::get('/class/{name}', [Factory::load(HomeController::class), 'index']);

// function api_with_name(string $name, Uri $uri) {
//     return new Response(json_encode([
//         'baseUrl' => $uri->baseUrl(),
//         'currentUrl' => $uri->currentUrl(),
//         'currentUri' => $uri->string(),
//         'segments' => $uri->segments(),
//         'params' => [
//             'name' => $name
//         ]
//     ], JSON_PRETTY_PRINT));
// }


function index(Uri $uri) {
    return new Response(json_encode([
        'baseUrl' => $uri->baseUrl(),
        'currentUrl' => $uri->currentUrl(),
        'currentUri' => $uri->string(),
        'segments' => $uri->segments()
    ], JSON_PRETTY_PRINT));
}

function routeFunction(string $name) {
    return new Response(json_encode([
            'name' => $name
        ], JSON_PRETTY_PRINT));
}



Route::get('/factory/{name}', [Factory::load(HomeController::class),'withFactory']);
Route::get('/noname', '\App\Controllers\HomeController@noName');
Route::get('/', '\App\Controllers\HomeController@index');
Route::get('/function/{name}','routeFunction');
Route::get('/inline/{name}',function($name){ 
    return new Response(json_encode([
        'inline' => $name
    ], JSON_PRETTY_PRINT));
});

Framework::run();