<?php 

namespace App\Middlewares;

class TestMiddleware {

    function testFunction(callable $next) {
        return $next();
    }

}