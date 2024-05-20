<?php

$mysqlConfig = json_decode(file_get_contents(BASEPATH. '/../private/.DBCONFIG_MYSQL'), true);
Selvi\Database\Manager::add('main', $mysqlConfig);
Selvi\Database\Migration::addMigration('main', BASEPATH.'/Migrations');
Selvi\Cli::register('migrate', Selvi\Database\Migration::class);