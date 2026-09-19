<?php

use Selvi\Factory;
use Selvi\Input\Uri;

if(!function_exists('currentUrl')) {
    function currentUrl(): string {
        $uri = Factory::resolve(Uri::class);
        return $uri->currentUrl();
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