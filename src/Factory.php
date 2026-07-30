<?php 

declare(strict_types=1);

namespace Selvi;

class Factory {

    public static array $instances = [];

    public static function resolve(string $className, array $knownParams = []): ?object {
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