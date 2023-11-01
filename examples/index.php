<?php 

require 'vendor/autoload.php';
define('BASEPATH', __DIR__);
Selvi\Route::get('/', 'HomeController@index');
Selvi\Framework::run();