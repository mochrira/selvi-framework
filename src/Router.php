<?php 

namespace Selvi;

use Selvi\Routing\RouteCollection;

class Router {

    static function resolve(string $method, string $uri) {
        $collection = Factory::resolve(RouteCollection::class);
        return $collection->match($method, $uri);
    }

}