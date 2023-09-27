<?php 

use Selvi\Route;
Route::get('/simple', 'AuthController@simple');
Route::get('/json', 'AuthController@json');
Route::get('/exception', 'AuthController@exception');