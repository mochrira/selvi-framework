<?php 

namespace Selvi\Routing;

use Closure;
use Selvi\Factory;
use Selvi\Utils\Arr;

class Route {

    private static $tmpMiddlewares = [];

    static function withMiddleware(array $middlewares, Closure $callback) {
        self::$tmpMiddlewares = Arr::removeDuplicates(self::$tmpMiddlewares, $middlewares);
        $callback();
        self::$tmpMiddlewares = [];
    }

    static function get(string $uri, callable | string | array $callback): Route {
        /** @var RouteCollection $collection */
        $collection = Factory::resolve(RouteCollection::class);
        $route = new Route('GET', $uri, $callback);
        $route->middleware(static::$tmpMiddlewares);
        return $collection->add($route);
    }

    static function post(string $uri, callable | string | array $callback): Route {
        /** @var RouteCollection $collection */
        $collection = Factory::resolve(RouteCollection::class);
        $route = new Route('POST', $uri, $callback);
        $route->middleware(static::$tmpMiddlewares);
        return $collection->add($route);
    }

    static function patch(string $uri, callable | string | array $callback): Route {
        /** @var RouteCollection $collection */
        $collection = Factory::resolve(RouteCollection::class);
        $route = new Route('PATCH', $uri, $callback);
        $route->middleware(static::$tmpMiddlewares);
        return $collection->add($route);
    }

    static function delete(string $uri, callable | string | array $callback): Route {
        /** @var RouteCollection $collection */
        $collection = Factory::resolve(RouteCollection::class);
        $route = new Route('DELETE', $uri, $callback);
        $route->middleware(static::$tmpMiddlewares);
        return $collection->add($route);
    }

    private $middlewares = [];
    private $params = [];

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

    function param($name, mixed $value = null) {
        if(is_null($value)) return $this->params[$name];
        $this->params[$name] = $value;
        return $this;
    }

    function middleware(Closure | array | string | null $middleware = null) {
        if(is_null($middleware)) return $this->middlewares;
        $this->middlewares = array_merge(
            $this->middlewares, 
            Arr::removeDuplicates(
                $this->middlewares, 
                is_array($middleware) ? $middleware : [$middleware]
            )
        );
        return $this;
    }

}