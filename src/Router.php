<?php 

namespace Selvi;

use Selvi\Routing\RouteInterface;

class Router {

    private static $routes = [];

    static function add(RouteInterface $route) {
        self::$routes[] = $route;
        return $route;
    }

    static function resolve(string $method, string $uri) {
        var_dump(self::$routes);
        foreach(self::$routes as $route) {
            /** @var RouteInterface $route*/
            $r = $route->match($method, $uri);
            if($r !== false) return $r;
        }
        return false;
    }

}