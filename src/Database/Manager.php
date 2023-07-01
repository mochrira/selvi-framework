<?php 

namespace Selvi\Database;

use Selvi\Database\Schema;

class Manager {

    private static $connections = [];

    public static function add($config, $name = 'default') {
        if(!isset(self::$connections[$name])) {
            self::$connections[$name] = new Schema($config);
        }
        return self::$connections[$name];
    }

    public static function get($name = 'default') {
        return isset(self::$connections[$name]) ? self::$connections[$name] : NULL;
    }

}