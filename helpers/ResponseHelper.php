<?php 

use Selvi\Response;
use Selvi\View;
use Selvi\Factory;

if(!function_exists('response')) {
    function response($data = '', $code = 200) {
        return new Response($data, $code);
    }
}


if(!function_exists('jsonResponse')) {
    function jsonResponse($data = '', $code = 200) {
        return new Response(json_encode($data, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT), $code);
    }
}

if(!function_exists('view')) {
    function view($file, $data = [], $returned = false) {
        $view = Factory::load(View::class, []);
        $content = $view->render($file, $data);
        if($returned) {
            return $content;
        }
        return response($content);
    }
}