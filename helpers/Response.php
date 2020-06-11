<?php 

use Selvi\Response;
use Selvi\View;
use Selvi\Factory;

function response($data = '', $code = 200) {
    return new Response($data, $code);
}

function jsonResponse($data = '', $code = 200) {
    return new Response(json_encode($data, JSON_PRETTY_PRINT), $code);
}

function view($file, $data = [], $returned = false) {
    $view = Factory::load(View::class, []);
    $content = $view->render($file, $data);
    if($returned) {
        return $content;
    }
    return response($content);
}