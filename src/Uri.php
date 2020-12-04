<?php 

namespace Selvi;

class Uri {

    private $uri;
    private $segments;

    public function __construct() {
        $this->uri = $_SERVER['REQUEST_URI'];
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