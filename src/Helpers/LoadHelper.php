<?php

use Selvi\Factory;
use Selvi\View;

if(!function_exists('inject')) {
    function inject(string $className): ?object {
        return Factory::resolve($className);
    }
}

if(!function_exists('view')) {
    function view(string $file, array $vars = []): View {
        $view = new View($file);
        foreach($vars as $key => $value) {
            $view->setVar($key, $value);
        }
        return $view;
    }
}