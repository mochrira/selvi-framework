<?php 

namespace Selvi\Routing;

use Closure;
use Selvi\Router;

class Route implements RouteInterface {

    private static ?RouteGroup $group = null;

    static function withMiddleware(Closure | array | string $middleware, Closure $callback) {
        return self::group($callback)->setMiddleware($middleware);
    }

    static function group(Closure $callback) {
        $group = new RouteGroup();
        $action = function ($parent = null) use ($callback, $group) {
            self::$group = $group;
            $callback();
            if($parent !== null) return $parent->add($group);
            self::$group = null;
            return Router::add($group);
        };
        return $action(self::$group);
    }

    private static function addRoute(string $method, string $uri, callable | string | array $callback) {
        $route = new Route($method, $uri, $callback);
        if(self::$group !== null) return self::$group->add($route);
        return Router::add($route);
    }

    static function get(string $uri, callable | string | array $callback): Route {
        return self::addRoute('GET', $uri, $callback);
    }

    static function post(string $uri, callable | string | array $callback): Route {
        return self::addRoute('POST', $uri, $callback);
    }

    static function patch(string $uri, callable | string | array $callback): Route {
        return self::addRoute('PATCH', $uri, $callback);
    }

    static function delete(string $uri, callable | string | array $callback): Route {
        return self::addRoute('DELETE', $uri, $callback);
    }

    static function options(string $uri, callable | string | array $callback): Route {
        return self::addRoute('OPTIONS', $uri, $callback);
    }

    private $middlewares = [];
    private $parameters = [];
    private $uriParams = [];

    function __construct(
        private string $method,
        private string $uri,
        private Closure | array | string $callback
    ) { }

    function getMethod() {
        return $this->method;
    }

    function getUri() {
        return $this->uri;
    }

    function getCallback() {
        return $this->callback;
    }

    function params(): array {
        return $this->parameters;
    }

    function getParam(string $name): mixed {
        return isset($this->parameters[$name]) ? $this->parameters[$name] : null;
    }

    function setParam(string $name, mixed $value): RouteInterface {
        $this->parameters[$name] = $value;
        return $this;
    }

    function setMiddleware(Closure | array | string $middleware): RouteInterface {
        $this->middlewares = array_merge(
            $this->middlewares, is_array($middleware) ? $middleware : [$middleware]
        );
        return $this;
    }

    function getMiddleware(): array {
        return $this->middlewares;
    }

    function match(string $method, string $uri, RouteGroup | null $parent = null) {
        if($this->method !== $method) return false;
        if (preg_match('#^' . preg_replace('/\{(.*?)\}/', '(.+)', $this->uri) . '$#', $uri, $values)) {
            if(preg_match('#^' . preg_replace('/\{(.*?)\}/', '\{(.+)\}', $this->uri) . '$#', $this->uri, $keys)) {
                array_shift($keys); array_shift($values);
                $this->uriParams = array_combine($keys, $values);
                return $this->compile($parent);
            }
        }
        return false;
    }

    function compile(RouteGroup | null $parent = null): Route {
        $route = new Route($this->method, $this->uri, $this->callback);
        $route->setMiddleware($this->getMiddleware());

        if($parent !== null) {
            $compiledParent = $parent->compile();
            $route->setMiddleware($compiledParent->getMiddleware());

            foreach($compiledParent->params() as $name => $value) {
                $route->setParam($name, $value);
            }
        }

        foreach($this->params() as $name => $value) {
            $route->setParam($name, $value);
        }

        foreach($this->uriParams as $name => $value) {
            $route->setParam($name, $value);
        }

        return $route;
    }

}