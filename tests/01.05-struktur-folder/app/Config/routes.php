<?php

use Selvi\Routing\Route;

Route::get('/', 'App\Controllers\HaloController@index');
Route::get('/sapa/{nama}', 'App\Controllers\HaloController@sapa');
