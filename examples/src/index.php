<?php 

require 'vendor/autoload.php';

use Selvi\Response;
use Selvi\Route;
use Selvi\Framework;

Route::get('/{name}/tab/{tab}', function (string $name, string $tab) {
    return new Response('name: '.$name.', tab: '.$tab);
});

Route::get('/', function () {
    return new Response('root');
});

Framework::run();