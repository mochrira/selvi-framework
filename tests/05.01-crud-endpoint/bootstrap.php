<?php

declare(strict_types=1);

use Selvi\Env;
use Selvi\Exception\Handler;

require __DIR__.'/../../vendor/autoload.php';

Env::load(__DIR__.'/../private/.ENV');
Handler::setDefaultHandlers();
Handler::listen();

require __DIR__.'/Config/database.php';