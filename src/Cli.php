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
        if(php_sapi_name() == 'cli') {
            try {
                global $argv;
                $name = $argv[1];
                $args = array_slice($argv, 2, count($argv));
                $response = self::$commands[$name]->run(...$args);
            } catch(Exception $e) {
                $response = jsonResponse([
                    'code' => $e->getErrorCode(),
                    'msg' => $e->getMessage()
                ], $e->getCode());
            }
            if(isset($response)) {
                $response->send();
            }
            die();
        }
    }

}