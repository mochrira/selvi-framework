<?php 

namespace Selvi;
use Selvi\Route;
use Selvi\Exception;
use Selvi\Cli;
use Selvi\Database\Migration;
use Selvi\View;

class Framework {

    public static function run() {
        Cli::register('migrate', Migration::class);
        Cli::listen();

        if(!isset(Route::$routes['get']['/'])) {
            View::setup(__DIR__.'/../views');
            Route::get('/', function() {
                return view('default');
            });
        }

        try {
            $callable = Route::getCallable();
            if(is_array($callable)) {
                $controller = new $callable[0];
                $response = $controller->{$callable[1]}();
            } else {
                if(is_callable($callable)) {
                    $response = $callable();
                }
            }
        } catch(Exception $e) {
            $konten = [
                'code' => $e->getErrorCode(),
                'msg' => $e->getMessage()
            ];
            if($e->getAdditionalData() !== null) {
                $konten['data'] = $e->getAdditionalData();
            }
            $response = jsonResponse($konten, $e->getCode());
        }

        if(isset($response)) {
            $response->send();
        }
    }

}