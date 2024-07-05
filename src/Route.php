<?php 

namespace Selvi;

use Closure;
use Selvi\Exception\HttpException;

class Route {

    private static $tmpMiddlewares = [];
    private static $routes = [
        'GET' => [],
        'POST' => [],
        'PATCH' => [],
        'DELETE' => []
    ];

    static private function removeDuplicates(array $existing, array $new) {
        $tmp = [];
        array_push($tmp, ...(
            array_filter($new, function ($v) use ($existing) {
                return !in_array($v, $existing, true);
            })
        ));
        return $tmp;
    }

    static function middleware(array $middlewares, Closure $callback) {
        self::$tmpMiddlewares = self::removeDuplicates(self::$tmpMiddlewares, $middlewares);
        $callback();
        self::$tmpMiddlewares = [];
    }

    static function get(string $uri, callable | string | array $callback, array $middlewares = []) {
        self::$routes['GET'][$uri] = [
            'cb' => $callback,
            'mid' => array_merge(self::$tmpMiddlewares, self::removeDuplicates(self::$tmpMiddlewares, $middlewares))
        ];
    }

    static function post(string $uri, callable | string | array $callback, array $middlewares = []) {
        self::$routes['POST'][$uri] = [
            'cb' => $callback,
            'mid' => array_merge(self::$tmpMiddlewares, self::removeDuplicates(self::$tmpMiddlewares, $middlewares))
        ];
    }

    static function patch(string $uri, callable | string | array $callback, array $middlewares = []) {
        self::$routes['PATCH'][$uri] = [
            'cb' => $callback,
            'mid' => array_merge(self::$tmpMiddlewares, self::removeDuplicates(self::$tmpMiddlewares, $middlewares))
        ];
    }

    static function delete(string $uri, callable | string | array $callback, array $middlewares = []) {
        self::$routes['DELETE'][$uri] = [
            'cb' => $callback,
            'mid' => array_merge(self::$tmpMiddlewares, self::removeDuplicates(self::$tmpMiddlewares, $middlewares))
        ];
    }

    static function translate(string $method, string $current_uri) {
        foreach (self::$routes[$method] as $route_uri => $options) {
            if (preg_match('#^' . preg_replace('/\{(.*?)\}/', '(.+)', $route_uri) . '$#', $current_uri, $values)) {
                if(preg_match('#^' . preg_replace('/\{(.*?)\}/', '\{(.+)\}', $route_uri) . '$#', $route_uri, $keys)) {
                    array_shift($keys); array_shift($values);
                    return ['cb' => $options['cb'], 'params' => array_combine($keys, $values), 'mid' => $options['mid']];
                }
            }
        }
        throw new HttpException("Route ".$method." '".$current_uri."'  not found", 404, $method, $current_uri);
    }

}