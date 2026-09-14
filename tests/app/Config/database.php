<?php

use Selvi\Database\DatabaseManager;
use Selvi\Database\DatabaseMigration;
use Selvi\Database\DatabaseSeeder;
use Selvi\Env;

DatabaseManager::add('main', [
    'driver' => Env::get('DB_DRIVER'),
    'host' => Env::get('DB_HOST'),
    'username' => Env::get('DB_USER'),
    'password' => Env::get('DB_PASS'),
    'database' => Env::get('DB_NAME')
]);

DatabaseMigration::add('main', BASEPATH.'/app/Migrations');
DatabaseSeeder::add('main', BASEPATH.'/app/Seeders');