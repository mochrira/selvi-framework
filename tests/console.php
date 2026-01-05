<?php

use Selvi\Database\Migration;
use Selvi\Database\Seeder;
use Selvi\Env;
use Symfony\Component\Console\Application;

require '../vendor/autoload.php';

define('BASEPATH', __DIR__);
Env::load(BASEPATH . '/private/.ENV');
require './app/Config/database.php';

$app = new Application('Selvi Commander', '1.0.0');
$app->addCommand(new Migration());
$app->addCommand(new Seeder());
$app->run();