<?php 

use Selvi\Routing\Route;

Route::post('/auth', 'Selvi\\Tests\\Controllers\\AuthController@getToken');
Route::get('/auth', 'Selvi\\Tests\\Controllers\\AuthController@info')->setMiddleware('Selvi\\Tests\\Middlewares\\AuthMiddleware@validateToken');
Route::patch('/auth', 'Selvi\\Tests\\Controllers\\AuthController@refreshToken')->setMiddleware('Selvi\\Tests\\Middlewares\\AuthMiddleware@validateRefreshToken');
 
Route::withMiddleware(['Selvi\\Tests\\Middlewares\\AuthMiddleware@validateToken'], function () {
    Route::get('/kontak', 'Selvi\\Tests\\Controllers\\KontakController@result');
    Route::get('/kontak/{id}', 'Selvi\\Tests\\Controllers\\KontakController@row');
    Route::post('/kontak', 'Selvi\\Tests\\Controllers\\KontakController@insert');
    Route::patch('/kontak/{id}', 'Selvi\\Tests\\Controllers\\KontakController@update');
    Route::delete('/kontak/{id}', 'Selvi\\Tests\\Controllers\\KontakController@delete'); 
    
    Route::get('/grup', 'Selvi\\Tests\\Controllers\\GrupController@result');
    Route::get('/grup/{id}', 'Selvi\\Tests\\Controllers\\GrupController@row');
    Route::post('/grup', 'Selvi\\Tests\\Controllers\\GrupController@insert');
    Route::patch('/grup/{id}', 'Selvi\\Tests\\Controllers\\GrupController@update');
    Route::delete('/grup/{id}', 'Selvi\\Tests\\Controllers\\GrupController@delete');
    
    Route::get('/produk', 'Selvi\\Tests\\Controllers\\ProdukController@result');
    Route::get('/produk/{idProduk}', 'Selvi\\Tests\\Controllers\\ProdukController@row');
    Route::post('/produk', 'Selvi\\Tests\\Controllers\\ProdukController@insert');
    Route::patch('/produk/{idProduk}', 'Selvi\\Tests\\Controllers\\ProdukController@update');
    Route::delete('/produk/{idProduk}', 'Selvi\\Tests\\Controllers\\ProdukController@delete');
    
    Route::get('/transaksi', 'Selvi\\Tests\\Controllers\\TransaksiController@result');
    Route::get('/transaksi/{idTransaksi}', 'Selvi\\Tests\\Controllers\\TransaksiController@row');
    Route::post('/transaksi', 'Selvi\\Tests\\Controllers\\TransaksiController@insert');
    Route::patch('/transaksi/{idTransaksi}', 'Selvi\\Tests\\Controllers\\TransaksiController@update');
    Route::delete('/transaksi/{idTransaksi}', 'Selvi\\Tests\\Controllers\\TransaksiController@delete');

    Route::post('/file', 'Selvi\\Tests\\Controllers\\FileController@upload');
});