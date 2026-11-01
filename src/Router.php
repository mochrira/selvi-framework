<?php 

declare(strict_types=1);

namespace Selvi;

use Selvi\Routing\Interfaces\IRoute;

class Router {

    private static array $routes = [];

    public static function add(IRoute $route): IRoute {
        self::$routes[] = $route;
        return $route;
    }

    public static function resolve(string $method, string $uri): IRoute|false {
        foreach(self::$routes as $route) {
            /** @var IRoute $route*/
            $r = $route->match($method, $uri);
            if($r !== false) return $r;
        }
        return false;
    }

}