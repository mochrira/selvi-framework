<?php 

use Selvi\Output\Response;
use Selvi\View;
use Selvi\Factory;
use Selvi\Output\JsonResponse;
use Selvi\Input\Uri;

if(!function_exists('response')) {
    function response($content = '', $code = 200) {
        return new Response($content, $code);
    }
}

if(!function_exists('jsonResponse')) {
    function jsonResponse($data = null, $code = 200, $options = JSON_PRETTY_PRINT) {
        return new JsonResponse($data, $code, $options);
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
        $uri = Factory::resolve(Uri::class, 'uri');
        return $uri->currentUrl();
    }
}

if(!function_exists('inject')) {
    function inject(mixed $className) {
        return Factory::resolve($className);
    }
}

if(!function_exists('baseUrl')) {
    function baseUrl() {
        $uri = Factory::resolve(Uri::class, 'uri');
        return $uri->baseUrl();
    }
}

if(!function_exists('siteUrl')) {
    function siteUrl($uri_string) {
        $uri = Factory::resolve(Uri::class, 'uri');
        return $uri->siteUrl($uri_string);
    }
}

if(!function_exists('redirect')) {
    function redirect($uri) {
        header('location:'.siteUrl($uri));
        exit();
    }
}