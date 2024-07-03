<?php 

namespace Selvi;

class Factory {

    public static $instances = [];

    static function resolve($className, $knownParams = []) {
        if(!isset(self::$instances[$className])) {
            $reflector = new \ReflectionClass($className);
            if(!$reflector->isInstantiable()) throw new \Exception('Could not resolve class '.$className);

            $constructor = $reflector->getConstructor();
            if(is_null($constructor)) {
                self::$instances[$className] = $reflector->newInstance();
            } else {
                $dependencies = Injector::getDependencies($constructor, $knownParams);
                self::$instances[$className] = $reflector->newInstanceArgs($dependencies);
            }
        }

        return self::$instances[$className] ?? null;
    }

}