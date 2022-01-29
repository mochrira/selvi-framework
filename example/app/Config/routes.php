<?php 

use Selvi\Route;

Route::get('/kontak', 'KontakController@result');
Route::get('/kontak/(:any)', 'KontakController@row');
Route::post('/kontak', 'KontakController@insert');
Route::patch('/kontak/(:any)', 'KontakController@update');
Route::delete('/kontak/(:any)', 'KontakController@delete');