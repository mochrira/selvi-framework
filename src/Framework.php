<?php 

namespace Selvi;

use Selvi\Factory;
use Selvi\Uri;
use Selvi\Request;
use Selvi\Route;

class Framework {

    static function run() {
        $uri = Factory::load(Uri::class);
        $request = Factory::load(Request::class);

        $result = Route::translate($request->method(), $uri->string());
        $methodRef = new \ReflectionFunction($result['callable']);
        
        $parameters = array_map(function ($parameter) use ($result) {
            $type = $parameter->getType();
            if($type->isBuiltIn()) {
                echo $parameter->name." is built-in \n";
            } else {
                echo $parameter->name." is not built-in \n";
            }
            return $result['params'][$parameter->name] ?? null;
        }, $methodRef->getParameters());

        $response = $result['callable'](...$parameters);
        $response->send();
    }

}