<?php 

namespace Selvi;
use Selvi\Base;
use Selvi\Exception;
use Selvi\Factory;

class Middleware extends Base {

    public static function compileCallable($middleware) {
        $callable = $middleware;
        if(!is_callable($callable)) {
            $callable = '\\App\\Middlewares\\'.$callable;
            return new $callable;
        }
        return $callable;
    }

}