<?php 

require '../vendor/autoload.php';
define('BASEPATH', __DIR__);

date_default_timezone_set('Asia/Jakarta');
header("Access-Control-Allow-Headers: Content-Type, Authorization, authorization");
header("Access-Control-Allow-Methods: OPTIONS, GET, POST, PATCH, DELETE");

require '../app/Config/exception.php';
require './Config/database.php';
require '../app/Config/routes.php';
Selvi\Framework::run();