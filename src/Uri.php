<?php 

namespace Selvi;

class Uri {

    private $uri;
    private $segments;

    public function __construct() {
        $sPos = strrpos($_SERVER['SCRIPT_NAME'], basename($_SERVER['SCRIPT_NAME']));
        $sDir = substr($_SERVER['SCRIPT_NAME'], 0, $sPos);
        $this->uri = preg_replace('/'.preg_quote($sDir, '/').'/', '/', $_SERVER['REQUEST_URI'], 1);
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

}