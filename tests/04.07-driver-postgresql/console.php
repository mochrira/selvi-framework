<?php

use Selvi\Database\Manager;
use Selvi\Database\Migration;
use Selvi\Env;
use Symfony\Component\Console\Application;

require __DIR__.'/../../vendor/autoload.php';

define('BASEPATH', __DIR__);
Env::load(__DIR__.'/../private/.ENV');

Manager::add('pgdemo', [
    'driver' => 'pgsql',
    'host' => Env::get('DB_PGSQL_HOST'),
    'port' => Env::get('DB_PGSQL_PORT', '5432'),
    'username' => Env::get('DB_PGSQL_USER'),
    'password' => Env::get('DB_PGSQL_PASS'),
    'database' => Env::get('DB_PGSQL_NAME'),
]);

Migration::add('pgdemo', __DIR__.'/migrations');

$app = new Application('Selvi Commander', '1.0.0');
$app->addCommand(new Migration());
$app->run();
