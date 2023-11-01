<?php

namespace Selvi;
use Selvi\Uri;
use Selvi\Input;
use Selvi\Factory;

class Route {

    public static $routes = [];
    public static $currentUri = '';
    public static $middlewares = [];

    public static function middleware($middlewares, callable $callback) {
        self::$middlewares = $middlewares;
        $callback();
        self::$middlewares = [];
    }

    public static function __callStatic($name, $args) {
        self::$routes[$name][$args[0]] = [
            'callback' => $args[1],
            'middlewares' => $args[2] ?? self::$middlewares ?? []
        ];
    }

    private static function matchPattern($routes) {
        foreach ($routes as $uri => $props) {
            $uri = str_replace(':any', '.+', $uri);
            $uri = str_replace(':num', '[0-9]+', $uri);
            $uri = str_replace(':nonum', '[^0-9]+', $uri);
            $uri = str_replace(':alpha', '[A-Za-z]+', $uri);
            $uri = str_replace(':alnum', '[A-Za-z0-9]+', $uri);
            $uri = str_replace(':hex', '[A-Fa-f0-9]+', $uri);
            if (preg_match('#^' . $uri . '$#', self::$currentUri)) {
                return $props;
            }
        }
        return null;
    }

    public static function compileCallable() {
        if(!isset(Route::$routes['get']['/'])) {
            View::setup(__DIR__.'/Views');
            Route::get('/', fn() => view('default'));
        }
        
        $input = Factory::load(Input::class, [], 'input');
        if($input->method() == 'OPTIONS') {
            http_response_code(200);
            die();
        }

        self::$currentUri = '/'.Factory::load(Uri::class, [], 'uri')->string();
        $routes = self::$routes[strtolower($input->method())];

        $route = $routes[self::$currentUri] ?? null;
        if($route == null) $route = self::matchPattern($routes);
        if($route == null) throw new Exception(null, null, 404);

        $callable = $route['callback'];
        if(is_string($callable) && strpos($callable, '@')) {
            $callable = explode('@', $callable);
            $callable[0] = class_exists($callable[0]) ? $callable[0] : '\\App\\Controllers\\'.$callable[0];
            $callable[0] = new $callable[0];
            $route['callable'] = $callable;
            return $route;
        }

        $route['callable'] = $callable;
        return $route;
    }

}