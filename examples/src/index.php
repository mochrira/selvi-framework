<?php 

declare(strict_types=1);

require 'vendor/autoload.php';

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

$config = json_decode(file_get_contents(__DIR__.'/private/.DBCONFIG'), true);
Manager::add('main', $config);

Route::get('/db', function () {
    $db = Manager::get('main');
    $connectRes = $db->connect();
    // $disconnectRes = $db->disconnect();
    $db->select_db('test');
    $queryKontak = $db->query("SELECT * FROM kontak");

    // $db->select(['kontak.nmKontak', 'kontak.idKontak'])->where([
    //     ['kontak.tunai', '<' , 1], ["kontak.nmKontak" , "Qur'an"]
    // ]);
    // $queryKontak = $db->get('kontak');

    return new Response(json_encode([
        'connect' => $connectRes,
        // 'disconnect' => $disconnectRes
        'kontak' => [
            'num_rows' => $queryKontak->num_rows(),
            'result' => $queryKontak->result(),
            'row' => $queryKontak->row()
        ]
    ], JSON_PRETTY_PRINT));
});

Framework::run();