<?php 

namespace Selvi\Routing;

use Closure;

class RouteGroup implements RouteInterface {

    private $routes = [];
    private $middlewares = [];
    private $parameters = [];

    private routeGroup | null  $parent = null;

    function getMiddleware(): array {
        return $this->middlewares;
    }

    function setMiddleware(Closure | array | string $middleware): RouteInterface {
        $this->middlewares = array_merge(
            $this->middlewares, is_array($middleware) ? $middleware : [$middleware]
        );
        return $this;
    }

    function add(RouteInterface $route) {
        $this->routes[] = $route;
        return $route;
    }

    function params(): array {
        return $this->parameters;
    }

    function getParam(string $name): mixed {
        return $this->parameters[$name];
    }

    function setParam(string $name, mixed $value): RouteInterface {
        $this->parameters[$name] = $value;
        return $this;
    }

    function match(string $method, string $uri, RouteGroup $parent = null) {
        $this->parent = $parent;
        foreach($this->routes as $route) {
            /** @var RouteInterface $route */
            $r = $route->match($method, $uri, $this);
            if($r !== false) return $r;
        }
        return false;
    }

    function compile() {
        $group = new RouteGroup();
        $group->setMiddleware($this->getMiddleware());

        if($this->parent !== null) {
            $compiledParent = $this->parent->compile();
            $group->setMiddleware($compiledParent->getMiddleware());

            foreach($compiledParent->params() as $name => $value) {
                $group->setParam($name, $value);
            }
        }

        foreach($this->params() as $name => $value) {
            $group->setParam($name, $value);
        }
        
        return $group;
    }

}