<?php

declare(strict_types=1);

use Selvi\Exception\Handler;

require __DIR__.'/../../vendor/autoload.php';

// Aplikasi nyata memetakan App\ ke app/ lewat autoload.psr-4 di composer.json.
// Folder uji ini hanya memuat autoload repo, jadi pemetaan itu didaftarkan manual.
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if(!str_starts_with($class, $prefix)) return;

    $path = __DIR__.'/app/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
    if(is_file($path)) require $path;
});

Handler::setDefaultHandlers();
Handler::listen();
