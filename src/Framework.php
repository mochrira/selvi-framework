<?php 

namespace Selvi;
use Selvi\Database\Migration;
use Selvi\Middleware;

class Framework {

    private static function executeRoute() {
        $route = Route::compileCallable();
        $action = function () use ($route) {
            return call_user_func($route['callable']);
        };

        $middlewares = $route['middlewares'];
        foreach($middlewares as $middleware) {
            $callable = Middleware::compileCallable($middleware);
            $action = function () use ($action, $callable) {
                return call_user_func($callable, $action);
            };
        }
        
        return $action();
    }

    public static function run() {
        try {
            if(php_sapi_name() == 'cli') Cli::listen()->send();
            self::executeRoute()->send();
        } catch(Exception $e) {
            $e->send();
        }
    }

}