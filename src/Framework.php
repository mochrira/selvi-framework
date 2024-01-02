<?php 

namespace Selvi;

use Selvi\Factory;
use Selvi\Uri;
use Selvi\Request;
use Selvi\Route;

class Framework {

    static function run() {
        $uri = Factory::load(Uri::class, 'uri');
        $request = Factory::load(Request::class, 'request');

        $result = Route::translate($request->method(), $uri->string());
        $methodRef = new \ReflectionFunction($result['callable']);
        
        $parameters = array_map(function ($parameter) use ($result) {
            return $result['params'][$parameter->name] ?? null;
        }, $methodRef->getParameters());

        $response = $result['callable'](...$parameters);
        $response->send();
    }

}