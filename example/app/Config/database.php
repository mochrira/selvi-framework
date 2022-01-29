<?php 

Selvi\Database\Manager::add([
    'host' => 'localhost',
    'username' => 'root',
    'password' => 'RDF?jq8eec',
    'database' => 'sample'
], 'main')->addMigration(__DIR__.'/../Migrations');