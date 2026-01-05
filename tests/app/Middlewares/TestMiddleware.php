<?php 

namespace Selvi\Tests\Middlewares;

class TestMiddleware {

    function testFunction(callable $next) {
        return $next();
    }

}