<?php 

require 'vendor/autoload.php';

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

// Route::get('/{name}', 'api_with_name');

function index(Uri $uri) {
    return new Response(json_encode([
        'baseUrl' => $uri->baseUrl(),
        'currentUrl' => $uri->currentUrl(),
        'currentUri' => $uri->string(),
        'segments' => $uri->segments()
    ], JSON_PRETTY_PRINT));
}

Route::get('/', '\App\Controllers\HomeController@index');

Framework::run();