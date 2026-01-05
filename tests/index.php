<?php

use Selvi\Env;

require '../vendor/autoload.php';

define('BASEPATH', __DIR__);
Env::load(BASEPATH.'/private/.ENV');

require './app/Config/exception.php';
require './app/Config/database.php';
require './app/Config/routes.php';

\Selvi\Framework::run();