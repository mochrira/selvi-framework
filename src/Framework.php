<?php 

namespace Selvi;

use Selvi\Exception\HttpException;
use Selvi\Input\Request;
use Selvi\Input\Uri;

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

        /** @var \Selvi\Routing\Route $route */
        $route = Router::resolve($method, $current_uri);
        if($route === false) throw new HttpException("Route ".$method." '".$current_uri."'  not found", 404, $method, $current_uri);

        $request->setRoute($route);
        $action = function () use ($route) {
            $ref = Injector::resolve($route->getCallback(), $route->params());
            return call_user_func($ref['cb'], ...$ref['params']);
        };

        foreach($route->getMiddleware() as $middleware) {
            $action = function () use ($middleware, $route, $action) {
                $params = $route->params();
                $params['next'] = $action;
                $ref = Injector::resolve($middleware, $params);
                return call_user_func($ref['cb'], ...$ref['params']);
            };
        }

        $action()->send();
    }

}