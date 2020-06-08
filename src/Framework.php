<?php 

namespace Selvi;
use Selvi\Route;
use Selvi\Exception;

class Framework {

    public static function run() {
        try {
            $callable = Route::getCallable();
            $controller = new $callable[0];
            $response = $controller->{$callable[1]}();
        } catch(Exception $e) {
            $response = jsonResponse([
                'code' => $e->getErrorCode(),
                'msg' => $e->getMessage()
            ], $e->getCode());
        }
        $response->send();
    }

}