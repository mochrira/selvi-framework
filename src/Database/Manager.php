<?php 

namespace Selvi\Database;

use Selvi\Database\Drivers\MySQL\MySQLSchema;
use Selvi\Database\Drivers\SQLSrv\SQLSrvSchema;

class Manager {

    private static Array $drivers = [
        'mysql' => MySQLSchema::class,
        'sqlsrv' => SQLSrvSchema::class
    ];

    private static Array $schemas = [];

    public static function add(string $name, Array $config): void {
        if(!isset(self::$schemas[$name])) self::$schemas[$name] = new self::$drivers[$config['driver']]($config);
    }

    public static function get(string $name): Schema {
        return self::$schemas[$name] ?? null;
    }

}