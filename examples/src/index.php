<?php 

declare(strict_types=1);

require 'vendor/autoload.php';
define('BASEPATH',__DIR__);

require('app/Config/database.php');

use Selvi\Response;
use Selvi\Route;
use Selvi\Framework;

/** Simplest way */
Route::get('/',function() { 
    return new Response('Index');
});


Framework::run();