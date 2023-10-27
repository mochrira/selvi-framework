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

    private static function matchPattern($routes, $uri_string) {
        foreach ($routes as $uri => $props) {
            $uri = str_replace(':any', '.+', $uri);
            $uri = str_replace(':num', '[0-9]+', $uri);
            $uri = str_replace(':nonum', '[^0-9]+', $uri);
            $uri = str_replace(':alpha', '[A-Za-z]+', $uri);
            $uri = str_replace(':alnum', '[A-Za-z0-9]+', $uri);
            $uri = str_replace(':hex', '[A-Fa-f0-9]+', $uri);
            if (preg_match('#^' . $uri . '$#', $uri_string)) {
                return $props;
                break;
            }
        }
        return null;
    }

    static function callable($method, $uri) {
        return self::matchPattern(self::$routes[$method], $uri);
    }

}