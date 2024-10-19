<?php 

namespace Selvi;

class Injector {

    public static function getMethodRef(mixed $callback) {
        if($callback instanceof \ReflectionMethod) return $callback;
        if($callback instanceof \ReflectionFunction) return $callback;
        if(is_array($callback)) return new \ReflectionMethod($callback[0], $callback[1]);
        return new \ReflectionFunction($callback);
    }

    public static function getDependencies(\ReflectionMethod | \ReflectionFunction $ref, $knownParams = []) {
        return array_map(function (\ReflectionParameter $param) use ($knownParams) {
            if(isset($knownParams[$param->getName()])) {
                return $knownParams[$param->getName()];
            }

            /** @var \ReflectionNamedType $type */
            $type = $param->getType();
            if(!$type->isBuiltin()) {
                return Factory::resolve($type->getName(), $knownParams);
            }

            if($param->isDefaultValueAvailable()) {
                return $param->getDefaultValue();
            } 

            if($param->allowsNull()) {
                return null;
            }
        }, $ref->getParameters());
    }

    public static function resolve(mixed $callback, array $knownParams = []) {
        if(is_string($callback) && strpos($callback, '@') !== false) {
            $defs = explode('@', $callback);
            $callback = [Factory::resolve($defs[0]), $defs[1]];
        }

        $ref = self::getMethodRef($callback);
        $params = self::getDependencies($ref, $knownParams);

        return [
            'cb' => $callback,
            'params' => $params
        ];
    }

}