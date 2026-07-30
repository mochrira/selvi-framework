<?php 

declare(strict_types=1);

namespace Selvi;
use Selvi\Factory;
use Selvi\Exception;

class Cli {

    private static array $commands = [];

    public static function register(string $command, string $cliClass): void {
        if(!isset(self::$commands[$command])) {
            self::$commands[$command] = Factory::resolve($cliClass);
        }
    }

    public static function listen(): mixed {
        try {
            global $argv;
            $name = $argv[1];
            $args = array_slice($argv, 2, count($argv));
            $response = self::$commands[$name]->run(...$args);
            return $response;
        } catch(Exception $e) {
            return response($e->getMessage());
        }
    }

}