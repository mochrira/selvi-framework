<?php

use Selvi\Database\DatabaseManager;
use Selvi\Env;
use Selvi\Routing\Route;

require '../vendor/autoload.php';

define('BASEPATH', __DIR__);
Env::load(BASEPATH.'/private/.ENV');

require './app/Config/exception.php';
require './app/Config/database.php';
require './app/Config/routes.php';

// Route::get('/kontak', function () {
//     $db = DatabaseManager::instance()->get('main');
//     $db->select();
// });

\Selvi\Framework::run();