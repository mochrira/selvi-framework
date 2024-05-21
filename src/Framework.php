<?php 

namespace Selvi;

use ReflectionNamedType;
use Selvi\Factory;
use Selvi\Uri;
use Selvi\Request;
use Selvi\Route;
use Selvi\Cli;

class Framework {

    static function run() {
        if(php_sapi_name() == 'cli') {
            Cli::listen()->send();
            die();
        }

        $uri = Factory::load(Uri::class);
        $request = Factory::load(Request::class);
        $result = Route::translate($request->method(), $uri->string());
        
        $callable = $result['callable'];
        if(is_callable($callable) && !is_array($callable)) {
            /** @var \ReflectionFunction  \ReflectionMethod  */
            if($callable instanceof \Closure || is_string($callable)) 
                $methodRef = new \ReflectionFunction($callable);
        } else {
            if(is_string($callable) && strpos($callable, '@') !== false) {
                $defs = explode('@', $result['callable']);
                $callable = [Factory::load($defs[0]), $defs[1]];
            }
            $methodRef = new \ReflectionMethod($callable[0], $callable[1]);
        }

        $parameters = array_map(function (\ReflectionParameter $parameter) use ($result) {
            if(isset($result['params'][$parameter->name])) 
                return $result['params'][$parameter->name];

            /** @var \ReflectionNamedType $type */
            $type = $parameter->getType();
            if($type->isBuiltin()) {
                if($parameter->isDefaultValueAvailable())
                    return $parameter->getDefaultValue();

                if($parameter->allowsNull())
                    return null;
            }

            return Factory::load($type->getName());
        }, $methodRef->getParameters());

        /** @var Response $response */
        $response = $callable(...$parameters);
        $response->send();
    }

}