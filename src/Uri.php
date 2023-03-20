<?php 

namespace Selvi;

class Uri {

    private static $baseUrl;

    public static function setBaseUrl($baseUrl) {
        self::$baseUrl = $baseUrl;
    }

    private $uri;
    private $segments;

    private $subFolder;
    private $scriptName;

    public function __construct() {
        $this->scriptName = $_SERVER['SCRIPT_NAME'];
        $sPos = strrpos($this->scriptName, basename($this->scriptName));
        $this->subFolder = substr($this->scriptName, 0, $sPos);

        $uri = $this->cleanUri($_SERVER['REQUEST_URI']);
        $this->uri = preg_replace('/'.preg_quote($this->subFolder, '/').'/', '/', $uri, 1);

        $baseUrl = $this->base_url();
        if(self::$baseUrl) $baseUrl = self::$baseUrl;

        $parsedUrl = parse_url($baseUrl);
        $basePath = $this->cleanUri($parsedUrl['path']);

        $this->uri = $this->cleanUri(preg_replace('/'.preg_quote($basePath, '/').'/', '', $this->uri, 1));

        $has_query = strpos($this->uri, '?');
        if($has_query !== false) {
            $this->uri = substr($this->uri, 0, $has_query);
        }
        $this->segments = explode('/', $this->uri);
    }

    private function cleanUri($uri) {
        return implode(array_reduce(explode('/', $uri), function ($carry, $item) {
            if(strlen($item) > 0) {
                $carry[] = $item;
            }
            return $carry;
        }, []), '/');
    }

    public function string() {
        return $this->uri;
    }

    public function segment($index)
	{
		return isset($this->segments[$index - 1]) ? $this->segments[$index - 1] : null;
	}

    public function base_url() {
        if(self::$baseUrl) return self::$baseUrl;
        return sprintf(
            "%s://%s",
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
            $_SERVER['HTTP_HOST']
        );
    }

}