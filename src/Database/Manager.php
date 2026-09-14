<?php 

declare(strict_types=1);

namespace Selvi\Database;

use Selvi\Database\Contracts\SchemaInterface;
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

    public static function has(string $name): bool {
        return isset(self::$schemas[$name]);
    }

    public static function get(string $name): SchemaInterface {
        return self::$schemas[$name] ?? null;
    }

    public static function default(): SchemaInterface | null {
        $keys = array_keys(self::$schemas);
        if(empty($keys)) return null;
        $name = $keys[0];
        return self::get($name);
    }

}