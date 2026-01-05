<?php

use Selvi\Database\Manager;
use Selvi\Database\Migration;
use Selvi\Database\Seeder;
use Selvi\Env;

Manager::add('main', [
    'driver' => Env::get('DB_DRIVER'),
    'host' => Env::get('DB_HOST'),
    'username' => Env::get('DB_USER'),
    'password' => Env::get('DB_PASS'),
    'database' => Env::get('DB_NAME')
]);

Migration::add('main', BASEPATH.'/app/Migrations');
Seeder::add('main', BASEPATH.'/app/Seeders');