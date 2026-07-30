<?php 

declare(strict_types=1);

namespace Selvi;

use Selvi\Routing\RouteInterface;

class Router {

    private static array $routes = [];

    public static function add(RouteInterface $route): RouteInterface {
        self::$routes[] = $route;
        return $route;
    }

    public static function resolve(string $method, string $uri): RouteInterface|false {
        foreach(self::$routes as $route) {
            /** @var RouteInterface $route*/
            $r = $route->match($method, $uri);
            if($r !== false) return $r;
        }
        return false;
    }

}