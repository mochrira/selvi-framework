<?php 

namespace Selvi;

use Selvi\Exception\HttpException;

class Route {

    private static $routes = [
        'GET' => [],
        'POST' => [],
        'PATCH' => [],
        'DELETE' => []
    ];

    static function get(string $uri, callable | string | array $callable) {
        self::$routes['GET'][$uri] = $callable;
    }

    static function post(string $uri, callable | string | array $callable) {
        self::$routes['POST'][$uri] = $callable;
    }

    static function patch(string $uri, callable | string | array $callable) {
        self::$routes['PATCH'][$uri] = $callable;
    }

    static function delete(string $uri, callable | string | array $callable) {
        self::$routes['DELETE'][$uri] = $callable;
    }

    static function translate(string $method, string $current_uri) {
        foreach (self::$routes[$method] as $route_uri => $callable) {
            if (preg_match('#^' . preg_replace('/\{(.*?)\}/', '(.+)', $route_uri) . '$#', $current_uri, $values)) {
                if(preg_match('#^' . preg_replace('/\{(.*?)\}/', '\{(.+)\}', $route_uri) . '$#', $route_uri, $keys)) {
                    array_shift($keys); array_shift($values);
                    return ['callable' => $callable, 'params' => array_combine($keys, $values)];
                }
            }
        }
        throw new HttpException("Route ".$method." '".$current_uri."'  not found", 404, $method, $current_uri);
    }

}