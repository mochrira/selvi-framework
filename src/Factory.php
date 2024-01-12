<?php 

namespace Selvi;

class Factory {

    private static $instances = [];

    static function load($className) {
        if(!isset(self::$instances[$className])) {
            self::$instances[$className] = new $className();
        }
        return self::$instances[$className];
    }

    // static function load($className, $alias) {
    //     if(!isset(self::$objects[$alias])) self::$objects[$alias] = new $className();
    //     return self::$objects[$alias];
    // }

    // static function get($name) {
    //     return self::$objects[$name] ?? null;
    // }

}