<?php 

use Selvi\Route;

Route::get('/kontak', '\\App\\Controllers\\KontakController@result');
Route::post('/kontak', '\\App\\Controllers\\KontakController@insert');

Route::get('/grup', '\\App\\Controllers\\GrupController@result');
Route::post('/grup', '\\App\\Controllers\\GrupController@insert');