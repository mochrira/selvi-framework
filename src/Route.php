<?php

namespace Selvi;
use Selvi\Uri;
use Selvi\Input;
use Selvi\Factory;

class Route {

    public static $routes = [];
    public static $currentUri = '';

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
        $classCall = explode('@', $callable);
        $classCall[0] = '\\App\\Controllers\\'.$classCall[0];
        if(is_callable($classCall)) {
            return $classCall;
        }
        return null;
    }

    public static function getCallable() {
        $input = Factory::load(Input::class, [], 'input');
        if($input->method() == 'OPTIONS') {
            http_response_code(200);
            die();
        }

        self::$currentUri = Factory::load(Uri::class, [], 'uri')->getUri();
        $routes = self::$routes[strtolower($input->method())];

        // Cek apakah route berupa function, jika function kembalikan nilai
        $callable = $routes[self::$currentUri];
        if(is_callable($callable)) {
            return $callable;
        }

        // Jika lolos dari tes diatas, bisa jadi berupa string class
        $callable = self::compileCallable($callable);
        if($callable !== null) {
            return $callable;
        }
        
        // Jika lolos dari semua test diatas, bisa jadi uri mengandung variabel
        foreach ($routes as $key => $val) {
            $key = str_replace(':any', '.+', $key);
            $key = str_replace(':num', '[0-9]+', $key);
            $key = str_replace(':nonum', '[^0-9]+', $key);
            $key = str_replace(':alpha', '[A-Za-z]+', $key);
            $key = str_replace(':alnum', '[A-Za-z0-9]+', $key);
            $key = str_replace(':hex', '[A-Fa-f0-9]+', $key);
            if (preg_match('#^' . $key . '$#', self::$currentUri)) {
                if (strpos($val, '$') !== false && strpos($key, '(') !== false) {
                    $val = preg_replace('#^' . $key . '$#', $val, self::$currentUri);
                }
                return self::compileCallable($val);
            }
        }
        
        // jika tidak ada kembalikan error
        Throw new Exception('Route tidak ditemukan. URI: '.self::$currentUri, 'route/not-found');
    }

}