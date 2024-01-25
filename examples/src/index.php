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
$configMysql = [
    "driver" => "mysql",
    "host"=>"mariadb.database",
    "username" => "root",
    "password" => "root"

];
Manager::add('slave', $configMysql);
Manager::add('main', $config);

Route::get('/db', function () {
    $db = Manager::get('main');
    $mysqlDb = Manager::get('slave');
    
    $db->connect();

    $mysqlDb->connect();
    $mysqlDb->select_db("test");

    // sql server
    $penggunaSqlServer = $db->limit(4)->get("Pengguna");


    // mysql
    $PenggunaMysql = $mysqlDb->order(cols:"Pengguna.PenggunaId")->limit(2)->offset(3)->get('Pengguna');

    return new Response(json_encode([
        // 'connect' => $connectRes,
        // 'disconnect' => $disconnectRes
        // 'kontak' => [
        //     'num_rows' => $queryKontak->num_rows(),
        //     'result' => $queryKontak->result(),
        //     'row' => $queryKontak->row()
        // ]
        'penggunaSQLServer'=> [
            'result'=>$penggunaSqlServer->result()
        ],
        'penggunaMYSQL'=> [
            'result'=>$PenggunaMysql->result()
        ]
    ], JSON_PRETTY_PRINT));
});

Framework::run();