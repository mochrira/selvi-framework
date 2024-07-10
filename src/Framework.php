<?php 

namespace Selvi;

use Selvi\Exception\HttpException;
use Selvi\Input\Request;
use Selvi\Input\Uri;
use Selvi\Routing\Route;
use Selvi\Routing\RouteGroup;

class RouteTest {

    static function group1() {
        $group = new RouteGroup();
            $group->add(new Route('GET', '/test/grup1', function () {
                return response('grup 1');
            }));
        return $group;
    }

    static function group2() {
        $group = new RouteGroup();
            $group->add(new Route('GET', '/test/grup2', function () {
                return response('grup 2');
            }));
        return $group;
    }

    static $parent;

    static function group($parent = null) {
        $group = new RouteGroup();
        if($parent !== null) return $parent->add($group);
        return Router::add($group);
    }

    static function get($method, $uri, $callback) {
        $route = new Route($method, $uri, $callback);
        if(self::$parent !== null) return self::$parent->add($route);
        return Router::add($route);
    }

}

class Framework {

    static function run() {
        if(php_sapi_name() == 'cli') {
            Cli::listen()->send();
            die();
        }

        $group = RouteTest::group();
        RouteTest::$parent = $group;
        RouteTest::get('GET', '/test/grup1', function () {
            return response('grup 1');
        });

        $group2 = RouteTest::group($group);
        RouteTest::$parent = $group2;
        RouteTest::get('GET', '/test/grup2', function () {
            return response('grup 2');
        });

        RouteTest::$parent = $group;
        RouteTest::get('GET', '/test/grup3', function () {
            return response('grup 3');
        });

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