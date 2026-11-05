<?php

require __DIR__.'/bootstrap.php';

use Selvi\Database\Migration;
use Symfony\Component\Console\Application;

Migration::add('main', __DIR__.'/Migrations');

$app = new Application('Selvi CRUD Demo', '1.0.0');
$app->addCommand(new Migration());
$app->run();
