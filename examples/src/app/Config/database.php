<?php

$mysqlConfig = json_decode(file_get_contents(BASEPATH. '/private/.DBCONFIG_MYSQL'), true);
Selvi\Database\Manager::add('mysql', $mysqlConfig);
Selvi\Database\Migration::addMigration('mysql', BASEPATH.'/app/Migrations/MySQL');

// $sqlsrvConfig = json_decode(file_get_contents(BASEPATH. '/private/.DBCONFIG_SQLSRV'), true);
// Selvi\Database\Manager::add('sqlsrv', $sqlsrvConfig);
// Selvi\Database\Migration::addMigration('sqlsrv', BASEPATH.'/app/Migrations/SQLSrv');

Selvi\Cli::register('migrate', Selvi\Database\Migration::class);