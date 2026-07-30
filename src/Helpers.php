<?php 

declare(strict_types=1);

use Selvi\Output\Response;
use Selvi\View;
use Selvi\Factory;
use Selvi\Output\JsonResponse;
use Selvi\Input\Uri;

if(!function_exists('response')) {
    function response(?string $content = '', int $code = 200): Response {
        return new Response($content, $code);
    }
}

if(!function_exists('jsonResponse')) {
    function jsonResponse(?array $data = null, int $code = 200, int $options = JSON_PRETTY_PRINT): JsonResponse {
        return new JsonResponse($data, $code, $options);
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

if(!function_exists('currentUrl')) {
    function currentUrl(): string {
        $uri = Factory::resolve(Uri::class);
        return $uri->currentUrl();
    }
}

if(!function_exists('inject')) {
    function inject(string $className): ?object {
        return Factory::resolve($className);
    }
}

if(!function_exists('baseUrl')) {
    function baseUrl(): string {
        $uri = Factory::resolve(Uri::class);
        return $uri->baseUrl();
    }
}

if(!function_exists('siteUrl')) {
    function siteUrl(string $uri_string): string {
        $uri = Factory::resolve(Uri::class);
        return $uri->siteUrl($uri_string);
    }
}

if(!function_exists('redirect')) {
    function redirect(string $uri): void {
        header('location:'.siteUrl($uri));
        exit();
    }
}