<?php 

require '../vendor/autoload.php';
define('BASEPATH', __DIR__);
require './Config/database.php';
require '../app/Config/routes.php';
Selvi\Framework::run();