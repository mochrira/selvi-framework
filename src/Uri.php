<?php 

namespace Selvi;

class Uri {

    private $baseUrl;
    private $currentUrl;

    private $uriString;
    private $uriSegments;

    function __construct() {
        $baseUrl = sprintf("%s://%s",
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
            $_SERVER['SERVER_NAME'] . ( $_SERVER['SERVER_PORT'] != '80' ? ':' . $_SERVER['SERVER_PORT'] : '' )
        );

        $this->currentUrl = $baseUrl . $_SERVER['REQUEST_URI'];
        $subDir = dirname($_SERVER['SCRIPT_NAME']);
        $this->baseUrl = $baseUrl . $subDir;

        $uriString = preg_replace('/'.preg_quote($subDir, '/').'/', '', $_SERVER['REQUEST_URI'], 1);
        $parts = explode('?', $uriString);

        $this->uriString = $this->validateUri($parts[0]);
        $this->uriSegments = $this->parseUri($this->uriString);
    }

    private function parseUri($uri) {
        $segments = explode('/', $uri);
        return array_reduce($segments, function ($carry, $item) {
            if(strlen($item) > 0) $carry[] = $item;
            return $carry;
        }, []);
    }

    private function validateUri($uri) {
        $segments = $this->parseUri($uri);
        return '/'.implode('/', $segments);
    }

    function baseUrl() {
        return $this->baseUrl;
    }

    function currentUrl() {
        return $this->currentUrl;
    }

    function siteUrl($uri) {
        return self::$baseUrl.$this->validateUri($uri);
    }

    function string() {
        return '/'.implode('/', $this->uriSegments);
    }

    function segments() {
        return $this->uriSegments;
    }

    function segment($index) {
        return $this->uriSegments[$index - 1] ?? null;
    }

}