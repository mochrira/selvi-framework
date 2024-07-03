<?php 

namespace Selvi;

class Framework {

    static function run() {
        if(php_sapi_name() == 'cli') {
            Cli::listen()->send();
            die();
        }

        $route = Route::translate(
            Factory::resolve(Request::class)->method(), 
            Factory::resolve(Uri::class)->string()
        );

        $action = function () use ($route) {
            $ref = Injector::resolve($route['cb'], $route['params']);
            return call_user_func($ref['cb'], ...$ref['params']);
        };

        foreach($route['mid'] as $middleware) {
            $route['params']['next'] = $action;
            $action = function () use ($middleware, $route) {
                $ref = Injector::resolve($middleware, $route['params']);
                return call_user_func($ref['cb'], ...$ref['params']);
            };
        }

        $action()->send();
    }

}