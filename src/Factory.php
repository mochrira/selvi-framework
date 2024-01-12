<?php 

namespace Selvi;

class Factory {

    public static $instances = [];

    static function load($className) {
        if(!isset(self::$instances[$className])) {
            self::$instances[$className] = self::resolve($className);
        }
        return self::$instances[$className] ?? null;
    }

    static function resolve($className) {
        $reflector = new \ReflectionClass($className);
        if(!$reflector->isInstantiable()) {
            throw new \Exception('Could not resolve class '.$className);
        }

        $constructor = $reflector->getConstructor();
        if(is_null($constructor)) {
            return $reflector->newInstance();
        }

        $params = $constructor->getParameters();
        $dependencies = self::getDependencies($params);
        return $reflector->newInstanceArgs($dependencies);
    }

    /** @var \ReflectionParameter[] $params */
    static function getDependencies($params) {
        $deps = [];
        foreach($params as $param) {
            /** @var \ReflectionNamedType $type */
            $type = $param->getType();
            if(!$type->isBuiltin()) {
                $deps[] = self::load($type->getName());
                continue;
            }

            if($param->isDefaultValueAvailable()) {
                $deps[] = $param->getDefaultValue();
                continue;
            } 

            if($param->allowsNull()) {
                $deps[] = null;
                continue;
            }
            
            throw new \Exception('Could not resolve dep with name '.$param->getName());
        }
        return $deps;
    }

}