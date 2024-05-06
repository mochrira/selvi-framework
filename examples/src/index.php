<?php 

define('BASEPATH',__DIR__);

require 'vendor/autoload.php';
require 'app/Config/database.php';
require 'app/Config/routes.php';

Selvi\Framework::run();