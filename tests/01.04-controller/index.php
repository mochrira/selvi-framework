<?php

require __DIR__.'/../../vendor/autoload.php';

// Folder uji tidak punya autoload PSR-4 untuk App\, jadi kelas dimuat manual.
require __DIR__.'/Services/SapaanService.php';
require __DIR__.'/Controllers/HaloController.php';

use Selvi\Routing\Route;

Route::get('/', 'App\Controllers\HaloController@index');
Route::get('/sapa/{nama}', 'App\Controllers\HaloController@sapa');
Route::get('/cari', 'App\Controllers\HaloController@cari');
Route::get('/urut/{kedua}/{pertama}', 'App\Controllers\HaloController@urut');
Route::get('/salah-nama/{name}', 'App\Controllers\HaloController@sapaSalah');
Route::get('/angka/{id}', 'App\Controllers\HaloController@angka');
Route::get('/tipe-angka/{id}', 'App\Controllers\HaloController@angkaTypeHint');
Route::get('/tanpa-return', 'App\Controllers\HaloController@tanpaReturn');
Route::get('/kelas-salah', 'App\Controllers\TidakAda@index');

Route::get('/info-route/{nama}', 'App\Controllers\HaloController@infoRoute');
Route::get('/param', 'App\Controllers\HaloController@param')
    ->setParam('nama', 'Nilai Param');

Route::patch('/metode', 'App\Controllers\HaloController@metode');
Route::delete('/metode', 'App\Controllers\HaloController@metode');
Route::options('/metode', 'App\Controllers\HaloController@metode');

Route::group(function () {
    Route::get('/grup', 'App\Controllers\HaloController@index');
});

Route::withMiddleware(function ($next) {
    $res = $next();
    $res->setContent($res->getContent().' + middleware');
    return $res;
}, function () {
    Route::get('/middleware', 'App\Controllers\HaloController@index');
});

Route::get('/middleware-route', 'App\Controllers\HaloController@index')
    ->setMiddleware(function ($next) {
        return response('Middleware route');
    });

\Selvi\Framework::run();
