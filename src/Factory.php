<?php 

namespace Selvi;

class Factory {

    private static $objects = [];

    static function load($className, $alias) {
        if(!isset(self::$objects[$alias])) self::$objects[$alias] = new $className();
        return self::$objects[$alias];
    }

    static function get($name) {
        return self::$objects[$name] ?? null;
    }

}