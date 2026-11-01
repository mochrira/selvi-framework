<?php

use Selvi\Database\Manager;
use Selvi\Database\Migration;
use Selvi\Env;
use Symfony\Component\Console\Application;

require __DIR__.'/../../vendor/autoload.php';

define('BASEPATH', __DIR__);

Env::load(__DIR__.'/../private/.ENV');

Manager::add('demo_migrasi', [
    'driver' => Env::get('DB_DRIVER', 'mysql'),
    'host' => Env::get('DB_HOST'),
    'port' => Env::get('DB_PORT'),
    'username' => Env::get('DB_USER'),
    'password' => Env::get('DB_PASS'),
    'database' => Env::get('DB_NAME'),
]);

Migration::add('demo_migrasi', __DIR__.'/migrations');

$app = new Application('Selvi Migration Demo', '1.0.0');
$app->addCommand(new Migration());
$app->run();
