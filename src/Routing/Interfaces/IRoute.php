<?php 

declare(strict_types=1);

namespace Selvi\Routing\Interfaces;

use Closure;
use Selvi\Routing\RouteGroup;

interface IRoute {

    function setMiddleware(Closure | array | string $middleware): IRoute;
    function getMiddleware(): array;
    function params(): array;
    function getParam(string $name): mixed;
    function setParam(string $name, mixed $value): IRoute;
    function match(string $method, string $uri, RouteGroup | null $parent = null);
    function compile(): IRoute;

}