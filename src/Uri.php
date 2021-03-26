<?php 

namespace Selvi;

class Uri {

    private $uri;
    private $segments;

    private $subFolder;
    private $scriptName;

    public function __construct() {
        $this->scriptName = $_SERVER['SCRIPT_NAME'];
        $sPos = strrpos($this->scriptName, basename($this->scriptName));
        $this->subFolder = substr($this->scriptName, 0, $sPos);
        $this->uri = preg_replace('/'.preg_quote($this->subFolder, '/').'/', '/', $_SERVER['REQUEST_URI'], 1);
        $has_query = strpos($this->uri, '?');
        if($has_query !== false) {
            $this->uri = substr($this->uri, 0, $has_query);
        }
        $this->segments = array_values(array_filter(explode('/', $this->uri)));
    }

    public function string() {
        return $this->uri;
    }

    public function segment($index)
	{
		return isset($this->segments[$index - 1]) ? $this->segments[$index - 1] : null;
	}

    public function base_url() {
        return sprintf(
            "%s://%s%s%s",
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
            $_SERVER['SERVER_NAME'],
            isset($_SERVER['SERVER_PORT']) ? ':' . $_SERVER['SERVER_PORT'] : '',
            $this->subFolder
        );
    }

}