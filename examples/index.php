<?php 

require 'vendor/autoload.php';
define('BASEPATH', __DIR__);

Selvi\Database\Manager::add([
    'host' => 'mariadb.database',
    'username' => 'root',
    'password' => 'RDF?jq8eec',
    'database' => 'test'
], 'main');

Selvi\Database\Migration::addMigration('main',  BASEPATH.'/app/Migrations');
Selvi\Cli::register('migrate', Selvi\Database\Migration::class);
Selvi\Route::get('/', 'HomeController@index');
Selvi\Framework::run();