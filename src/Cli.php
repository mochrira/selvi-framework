<?php 

namespace Selvi;
use Selvi\Factory;
use Selvi\Exception;

class Cli {

    private static $commands = [];

    public static function register($command, $cliClass) {
        if(!isset(self::$commands[$command])) {
            self::$commands[$command] = Factory::load($cliClass);
        }
    }

    public static function listen() {
        try {
            global $argv;
            $name = $argv[1];
            $args = array_slice($argv, 2, count($argv));
            $response = self::$commands[$name]->run(...$args);
            return $response;
        } catch(Exception $e) {
            throw $e;
        }
    }

}