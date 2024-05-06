<?php

$dbConfig = json_decode(file_get_contents(BASEPATH. '/private/.DBCONFIG_MYSQL'), true);
Selvi\Database\Manager::add('mysql', $dbConfig);
Selvi\Database\Manager::get('mysql')->connect();
Selvi\Database\Migration::addMigration('mysql', BASEPATH.'/app/Migrations');
Selvi\Cli::register('migrate', Selvi\Database\Migration::class);