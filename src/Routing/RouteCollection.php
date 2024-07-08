<?php 

namespace Selvi\Routing;

use SplObjectStorage;

class RouteCollection extends SplObjectStorage {

    function add(Route $route): Route {
        parent::attach($route);
        return $route;
    }

    function match(string $method, string $uri) {
        $this->rewind();
        while($this->valid()) {
            /** @var \Selvi\Routing\Route $route */
            $route = $this->current();
            $this->next();
            if($route->getMethod() == $method) {
                $route_uri = $route->getUri();
                if (preg_match('#^' . preg_replace('/\{(.*?)\}/', '(.+)', $route_uri) . '$#', $uri, $values)) {
                    if(preg_match('#^' . preg_replace('/\{(.*?)\}/', '\{(.+)\}', $route_uri) . '$#', $route_uri, $keys)) {
                        array_shift($keys); array_shift($values);
                        $route->param('uri', array_combine($keys, $values));
                        return $route;
                    }
                }
            }
        }
        return null;
    }

}