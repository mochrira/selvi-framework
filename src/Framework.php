<?php 

namespace Selvi;

use Selvi\Exception\HttpException;

class Framework {

    static function run() {
        if(php_sapi_name() == 'cli') {
            Cli::listen()->send();
            die();
        }

        /** @var \Selvi\Request $request */
        $request = Factory::resolve(Request::class);
        $method = $request->method();

        /** @var \Selvi\Uri $uri */
        $uri = Factory::resolve(Uri::class);
        $current_uri = $uri->string();

        /** @var \Selvi\Router $router */
        $router = Factory::resolve(Router::class);

        /** @var \Selvi\Routing\Route $route */
        $route = $router->resolve($method, $current_uri);
        if($route == null) throw new HttpException("Route ".$method." '".$current_uri."'  not found", 404, $method, $current_uri);

        $request->setRoute($route);
        $action = function () use ($route) {
            $ref = Injector::resolve($route->getCallback(), $route->param('uri'));
            return call_user_func($ref['cb'], ...$ref['params']);
        };

        foreach($route->middleware() as $middleware) {
            $action = function () use ($middleware, $route, $action) {
                $params = $route->param('uri');
                $params['next'] = $action;
                $ref = Injector::resolve($middleware, $params);
                return call_user_func($ref['cb'], ...$ref['params']);
            };
        }

        $action()->send();
    }

}