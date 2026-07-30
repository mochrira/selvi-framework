<?php 

declare(strict_types=1);

namespace Selvi\Routing;

use Closure;
use Selvi\Router;

class Route implements RouteInterface {

    private static ?RouteGroup $parent = null;

    public static function withMiddleware(Closure | array | string $middleware, Closure $callback): RouteGroup {
        return self::group($callback)->setMiddleware($middleware);
    }

    public static function group(Closure $callback): RouteGroup {
        $group = new RouteGroup();
        $action = function ($parent) use ($group, $callback) {
            self::$parent = $group;
            $callback();
            self::$parent = $parent;
            if($parent !== null) return $parent->add($group);
            return Router::add($group);
        };
        return $action(self::$parent);
    }

    private static function addRoute(string $method, string $uri, callable | string | array $callback): Route {
        $route = new Route($method, $uri, $callback);
        if(self::$parent !== null) return self::$parent->add($route);
        return Router::add($route);
    }

    public static function get(string $uri, callable | string | array $callback): Route {
        return self::addRoute('GET', $uri, $callback);
    }

    public static function post(string $uri, callable | string | array $callback): Route {
        return self::addRoute('POST', $uri, $callback);
    }

    public static function patch(string $uri, callable | string | array $callback): Route {
        return self::addRoute('PATCH', $uri, $callback);
    }

    public static function delete(string $uri, callable | string | array $callback): Route {
        return self::addRoute('DELETE', $uri, $callback);
    }

    public static function options(string $uri, callable | string | array $callback): Route {
        return self::addRoute('OPTIONS', $uri, $callback);
    }

    private array $middlewares = [];
    private array $parameters = [];
    private array $uriParams = [];

    public function __construct(
        private string $method,
        private string $uri,
        private Closure | array | string $callback
    ) { }

    public function getMethod(): string {
        return $this->method;
    }

    public function getUri(): string {
        return $this->uri;
    }

    public function getCallback(): Closure|array|string {
        return $this->callback;
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

    public function setMiddleware(Closure | array | string $middleware): RouteInterface {
        $this->middlewares = array_merge(
            $this->middlewares, is_array($middleware) ? $middleware : [$middleware]
        );
        return $this;
    }

    public function getMiddleware(): array {
        return $this->middlewares;
    }

    public function match(string $method, string $uri, RouteGroup | null $parent = null): Route|false {
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

    public function compile(RouteGroup | null $parent = null): Route {
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