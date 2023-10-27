<?php 

namespace Selvi;

class Uri {

    private static $baseUrl;

    public static function setBaseUrl($baseUrl) {
        if(substr($baseUrl, -1) == '/') {
            $baseUrl = substr($baseUrl, 0, strlen($baseUrl) - 1);
        }
        self::$baseUrl = $baseUrl;
    }

    function baseUrl() {
        return self::$baseUrl.'/';
    }

    private $currentUrl;
    private $segments;

    function __construct() {
        if(!self::$baseUrl) self::$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://').
            $_SERVER['HTTP_HOST'];

        echo self::$baseUrl;
        die();
        
        $parts = explode('?', $_SERVER['REQUEST_URI']);
        $requestUri = $parts[0];

        $this->currentUrl = self::$baseUrl.$requestUri;
        $uri = preg_replace('/'.preg_quote(self::$baseUrl, '/').'/', '', $this->currentUrl);
        var_dump($uri);
        die();
        $this->segments = $this->parseUri($uri);
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

    function currentUrl() {
        return $this->currentUrl;
    }

    function siteUrl($uri) {
        return self::$baseUrl.$this->validateUri($uri);
    }

    function string() {
        return '/'.implode('/', $this->segments);
    }

    function segments() {
        return $this->segments;
    }

    function segment($index) {
        return $this->segments[$index - 1] ?? null;
    }

}