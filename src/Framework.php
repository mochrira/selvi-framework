<?php 

namespace Selvi;

use ReflectionNamedType;
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
        
        $parameters = array_map(function (\ReflectionParameter $parameter) use ($result) {
            if(isset($result['params'][$parameter->name])) 
                return $result['params'][$parameter->name];

            if($parameter->isDefaultValueAvailable())
                return $parameter->getDefaultValue();

            /** @var \ReflectionNamedType $type */
            $type = $parameter->getType();
            return Factory::load($type->getName());
        }, $methodRef->getParameters());

        $response = $result['callable'](...$parameters);
        $response->send();
    }

}