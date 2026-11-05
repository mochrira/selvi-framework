<?php 

use Selvi\Routing\Route;

Route::get('/produk', 'App\Controllers\ProdukController@result');
Route::get('/produk/{id}', 'App\Controllers\ProdukController@row');
Route::post('/produk', 'App\Controllers\ProdukController@insert');
Route::patch('/produk/{id}', 'App\Controllers\ProdukController@update');
Route::delete('/produk/{id}', 'App\Controllers\ProdukController@delete');