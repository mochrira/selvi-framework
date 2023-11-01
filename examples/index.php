<?php 

require 'vendor/autoload.php';
define('BASEPATH', __DIR__);

$dbConfig = json_decode(file_get_contents(BASEPATH.'/private/.DBCONFIG'), true);
Selvi\Database\Manager::add($dbConfig, 'main');
Selvi\Database\Migration::addMigrations('main', [ BASEPATH.'/app/Migrations' ]);
Selvi\Cli::register('migrate', Selvi\Database\Migration::class);

require 'app/Config/routes.php';
Selvi\Framework::run();