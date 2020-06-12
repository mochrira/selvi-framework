<?php

namespace Selvi;
use Selvi\Uri;
use Selvi\Input;
use Selvi\Factory;

class Route {

    public static $routes = [];

    public static function __callStatic($name, $args) {
        self::$routes[$name][$args[0]] = $args[1];
    }

    public static function apiResource($name, $controller, $allowed = ['get', 'post', 'patch', 'delete']) {
        if(in_array('get', $allowed)) { self::get('/'.$name, $controller.'@get'); }
        if(in_array('get', $allowed)) { self::get('/'.$name.'/(:any)', $controller.'@get'); }
        if(in_array('post', $allowed)) { self::post('/'.$name, $controller.'@post'); }
        if(in_array('patch', $allowed)) { self::patch('/'.$name.'/(:any)', $controller.'@patch'); }
        if(in_array('delete', $allowed)) { self::delete('/'.$name.'/(:any)', $controller.'@delete'); }
    }

    private static function compileCallable($callable) {
        if(!is_callable($callable)){
            $callable[0] = '\\App\\Controllers\\'.$callable[0];
        }
        if(!is_callable($callable)) {
            return NULL;
        }
        return $callable;
    }

    public static function getCallable() {
        $input = Factory::load(Input::class, [], 'input');
        $uri = Factory::load(Uri::class, [], 'uri')->getUri();
        $routes = self::$routes[strtolower($input->method())];
        
        if (isset($routes[$uri])) {
            return self::compileCallable(explode('@', $routes[$uri]));
        }
        
        foreach ($routes as $key => $val) {
            $key = str_replace(':any', '.+', $key);
            $key = str_replace(':num', '[0-9]+', $key);
            $key = str_replace(':nonum', '[^0-9]+', $key);
            $key = str_replace(':alpha', '[A-Za-z]+', $key);
            $key = str_replace(':alnum', '[A-Za-z0-9]+', $key);
            $key = str_replace(':hex', '[A-Fa-f0-9]+', $key);
            if (preg_match('#^' . $key . '$#', $uri)) {
                if (strpos($val, '$') !== false && strpos($key, '(') !== false) {
                    $val = preg_replace('#^' . $key . '$#', $val, $uri);
                }
                return self::compileCallable(explode('@', $val));
            }
        }
        return self::compileCallable(explode('@', $uri));
    }

}