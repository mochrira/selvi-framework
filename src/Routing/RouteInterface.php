<?php 

namespace Selvi\Routing;

use Closure;

interface RouteInterface {

    function setMiddleware(Closure | array | string $middleware): RouteInterface;
    function getMiddleware(): array;
    function params(): array;
    function getParam(string $name): mixed;
    function setParam(string $name, mixed $value): RouteInterface;
    function match(string $method, string $uri, RouteGroup | null $parent = null);

}