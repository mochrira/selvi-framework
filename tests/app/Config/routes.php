<?php 

use Selvi\Routing\Route;

Route::get('/kontak/{metode}/{idKontak}', 'Selvi\\Tests\\Controllers\\KontakController@row');
Route::get('/kontak/{metode}', 'Selvi\\Tests\\Controllers\\KontakController@result');
Route::post('/kontak/{metode}', 'Selvi\\Tests\\Controllers\\KontakController@insert');
Route::patch('/kontak/{metode}/{idKontak}', 'Selvi\\Tests\\Controllers\\KontakController@update');
Route::delete('/kontak/{metode}/{idKontak}', 'Selvi\\Tests\\Controllers\\KontakController@delete');

Route::get('/grup', 'Selvi\\Tests\\Controllers\\GrupController@result');
Route::get('/grup/{idGrup}', 'Selvi\\Tests\\Controllers\\GrupController@row');
Route::post('/grup', 'Selvi\\Tests\\Controllers\\GrupController@insert');
Route::patch('/grup/{idGrup}', 'Selvi\\Tests\\Controllers\\GrupController@update');
Route::delete('/grup/{idGrup}', 'Selvi\\Tests\\Controllers\\GrupController@delete');

Route::get('/departemen', 'Selvi\\Tests\\Controllers\\DepartemenController@result');
Route::get('/departemen/{idDepartemen}', 'Selvi\\Tests\\Controllers\\DepartemenController@row');
Route::post('/departemen', 'Selvi\\Tests\\Controllers\\DepartemenController@insert');
Route::patch('/departemen/{idDepartemen}', 'Selvi\\Tests\\Controllers\\DepartemenController@update');
Route::delete('/departemen/{idDepartemen}', 'Selvi\\Tests\\Controllers\\DepartemenController@delete');