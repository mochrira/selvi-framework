<?php 

define('BASEPATH', __DIR__);
require 'vendor/autoload.php';
Selvi\Route::get('/', 'HomeController@index');
Selvi\Framework::run();