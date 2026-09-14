<?php 

use Selvi\Routing\Route;

Route::get('/kontak', 'Selvi\\Tests\\Controllers\\KontakController@result');
Route::get('/kontak/{idKontak}', 'Selvi\\Tests\\Controllers\\KontakController@row');
Route::post('/kontak', 'Selvi\\Tests\\Controllers\\KontakController@insert');
Route::patch('/kontak/{idKontak}', 'Selvi\\Tests\\Controllers\\KontakController@update');
Route::delete('/kontak/{idKontak}', 'Selvi\\Tests\\Controllers\\KontakController@delete');

Route::get('/grup', 'Selvi\\Tests\\Controllers\\GrupController@result');
Route::get('/grup/{idGrup}', 'Selvi\\Tests\\Controllers\\GrupController@row');
Route::post('/grup', 'Selvi\\Tests\\Controllers\\GrupController@insert');
Route::patch('/grup/{idGrup}', 'Selvi\\Tests\\Controllers\\GrupController@update');
Route::delete('/grup/{idGrup}', 'Selvi\\Tests\\Controllers\\GrupController@delete');