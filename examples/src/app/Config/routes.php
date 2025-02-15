<?php 

use Selvi\Routing\Route;

Route::post('/auth', 'App\\Controllers\\AuthController@getToken');
Route::get('/auth', 'App\\Controllers\\AuthController@info')->setMiddleware('App\\Middlewares\\AuthMiddleware@validateToken');
Route::patch('/auth', 'App\\Controllers\\AuthController@refreshToken')->setMiddleware('App\\Middlewares\\AuthMiddleware@validateRefreshToken');
 
Route::withMiddleware(['App\\Middlewares\\AuthMiddleware@validateToken'], function () {
    Route::get('/kontak', 'App\\Controllers\\KontakController@result');
    Route::get('/kontak/{id}', 'App\\Controllers\\KontakController@row');
    Route::post('/kontak', 'App\\Controllers\\KontakController@insert');
    Route::patch('/kontak/{id}', 'App\\Controllers\\KontakController@update');
    Route::delete('/kontak/{id}', 'App\\Controllers\\KontakController@delete'); 
    
    Route::get('/grup', 'App\\Controllers\\GrupController@result');
    Route::get('/grup/{id}', 'App\\Controllers\\GrupController@row');
    Route::post('/grup', 'App\\Controllers\\GrupController@insert');
    Route::patch('/grup/{id}', 'App\\Controllers\\GrupController@update');
    Route::delete('/grup/{id}', 'App\\Controllers\\GrupController@delete');
    
    Route::get('/produk', 'App\\Controllers\\ProdukController@result');
    Route::get('/produk/{idProduk}', 'App\\Controllers\\ProdukController@row');
    Route::post('/produk', 'App\\Controllers\\ProdukController@insert');
    Route::patch('/produk/{idProduk}', 'App\\Controllers\\ProdukController@update');
    Route::delete('/produk/{idProduk}', 'App\\Controllers\\ProdukController@delete');
    
    Route::get('/transaksi', 'App\\Controllers\\TransaksiController@result');
    Route::get('/transaksi/{idTransaksi}', 'App\\Controllers\\TransaksiController@row');
    Route::post('/transaksi', 'App\\Controllers\\TransaksiController@insert');
    Route::patch('/transaksi/{idTransaksi}', 'App\\Controllers\\TransaksiController@update');
    Route::delete('/transaksi/{idTransaksi}', 'App\\Controllers\\TransaksiController@delete');

    Route::post('/file', 'App\\Controllers\\FileController@upload');
});