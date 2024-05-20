<?php

$mysqlConfig = json_decode(file_get_contents(BASEPATH. '/private/.DBCONFIG_MYSQL'), true);
Selvi\Database\Manager::add('mysql', $mysqlConfig);
Selvi\Database\Migration::addMigration('mysql', BASEPATH.'/app/Migrations/MySQL');
Selvi\Cli::register('migrate', Selvi\Database\Migration::class);