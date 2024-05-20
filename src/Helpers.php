<?php 

use Selvi\Response;
use Selvi\View;
use Selvi\Factory;
use Selvi\Uri;

if(!function_exists('response')) {
    function response($content = '', $code = 200) {
        return new Response($content, $code);
    }
}

if(!function_exists('jsonResponse')) {
    function jsonResponse($content = null, $code = 200, $options = JSON_PRETTY_PRINT) {
        return response($content !== null ? json_encode($content, $options) : null, $code);
    }
}

if(!function_exists('view')) {
    function view($file, $vars = []) {
        $view = new View($file);
        foreach($vars as $key => $value) {
            $view->setVar($key, $value);
        }
        return $view;
    }
}

if(!function_exists('currentUrl')) {
    function currentUrl() {
        $uri = Factory::load(Uri::class, 'uri');
        return $uri->currentUrl();
    }
}

if(!function_exists('baseUrl')) {
    function baseUrl() {
        $uri = Factory::load(Uri::class, 'uri');
        return $uri->baseUrl();
    }
}

if(!function_exists('siteUrl')) {
    function siteUrl($uri_string) {
        $uri = Factory::load(Uri::class, 'uri');
        return $uri->siteUrl($uri_string);
    }
}

if(!function_exists('redirect')) {
    function redirect($uri) {
        header('location:'.siteUrl($uri));
        exit();
    }
}