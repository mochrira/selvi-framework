<?php 

namespace Selvi;
use Selvi\Route;

class Framework {

    public static function run() {
        $callable = Route::getCallable();
        if($callable !== NULL) {
            $controller = new $callable[0];
            $controller->{$callable[1]}();
        } else {
            \http_response_code(404);
        }
    }

}