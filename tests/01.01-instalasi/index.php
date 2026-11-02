<?php

require __DIR__.'/vendor/autoload.php';

\Selvi\Routing\Route::get('/', function () {
    return response('Halo, Selvi Framework!');
});

\Selvi\Framework::run();
