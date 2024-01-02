<?php 

namespace Selvi;

class Route {

    private static $routes = [
        'GET' => [],
        'POST' => [],
        'PATCH' => [],
        'DELETE' => []
    ];

    static function get($uri, $callable) {
        self::$routes['GET'][$uri] = $callable;
    }

    static function post($uri, $callable) {
        self::$routes['POST'][$uri] = $callable;
    }

    static function patch($uri, $callable) {
        self::$routes['PATCH'][$uri] = $callable;
    }

    static function delete($uri, $callable) {
        self::$routes['DELETE'][$uri] = $callable;
    }

    static function bind($uri, $callable) {
        self::get($uri, $callable);
        self::post($uri, $callable);
        self::patch($uri, $callable);
        self::delete($uri, $callable);
    }

    static function translate($method, $current_uri) {
        foreach (self::$routes[$method] as $route_uri => $callable) {
            if (preg_match('#^' . preg_replace('/\{(.*?)\}/', '(.+)', $route_uri) . '$#', $current_uri, $values)) {
                if(preg_match('#^' . preg_replace('/\{(.*?)\}/', '\{(.+)\}', $route_uri) . '$#', $route_uri, $keys)) {
                    array_shift($keys); array_shift($values);
                    return ['callable' => $callable, 'params' => array_combine($keys, $values)];
                }
            }
        }
        return null;
    }

}