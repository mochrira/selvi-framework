<?php 

namespace Selvi\Database;

use Selvi\Database\Drivers\MySQLDriver;
use Selvi\Database\Drivers\SQLServerDriver;

class Manager {

    private static Array $drivers = [
        'mysql' => MySQLDriver::class,
        'sqlsrv' => SQLServerDriver::class
    ];

    private static Array $schemas = [];

    public static function add(string $name, Array $config, string $driver = 'mysql'): void {
        if(!isset(self::$schemas[$name])) self::$schemas[$name] = new self::$drivers[$driver]($config);
    }

    public static function get(string $name): Schema {
        return self::$schemas[$name] ?? null;
    }

}