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

\Selvi\Framework::run();
