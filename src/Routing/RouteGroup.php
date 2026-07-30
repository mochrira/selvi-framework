<?php 

declare(strict_types=1);

namespace Selvi\Routing;

use Closure;

class RouteGroup implements RouteInterface {

    private array $routes = [];
    private array $middlewares = [];
    private array $parameters = [];

    private ?RouteGroup $parent = null;

    public function getMiddleware(): array {
        return $this->middlewares;
    }

    public function setMiddleware(Closure | array | string $middleware): RouteInterface {
        $this->middlewares = array_merge(
            $this->middlewares, is_array($middleware) ? $middleware : [$middleware]
        );
        return $this;
    }

    public function add(RouteInterface $route): RouteInterface {
        $this->routes[] = $route;
        return $route;
    }

    public function params(): array {
        return $this->parameters;
    }

    public function getParam(string $name): mixed {
        return isset($this->parameters[$name]) ? $this->parameters[$name] : null;
    }

    public function setParam(string $name, mixed $value): RouteInterface {
        $this->parameters[$name] = $value;
        return $this;
    }

    public function match(string $method, string $uri, ?RouteGroup $parent = null): RouteInterface|false {
        $this->parent = $parent;
        foreach($this->routes as $route) {
            /** @var RouteInterface $route */
            $r = $route->match($method, $uri, $this);
            if($r !== false) return $r;
        }
        return false;
    }

    public function compile(): RouteGroup {
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