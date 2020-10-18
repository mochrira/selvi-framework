<?php 

namespace Selvi;

class Uri {

    private $uri;
    private $segments;

    public function __construct() {
        $host = $_SERVER['HTTP_HOST'];

        $this->uri = parse_url('http://dummy'.$_SERVER['REQUEST_URI']);
		$this->uri = isset($this->uri['path']) ? $this->uri['path'] : '';

		if (isset($_SERVER['SCRIPT_NAME'][0]))
		{
			if (strpos($this->uri, $_SERVER['SCRIPT_NAME']) === 0)
			{
				$this->uri = (string) substr($this->uri, strlen($_SERVER['SCRIPT_NAME']));
			}
			elseif (strpos($this->uri, dirname($_SERVER['SCRIPT_NAME'])) === 0)
			{
				$this->uri = (string) substr($this->uri, strlen(dirname($_SERVER['SCRIPT_NAME'])));
			}
        }
        
        $has_query = strpos($this->uri, '?');
        if($has_query !== false) {
            $this->uri = substr($this->uri, 0, $has_query);
        }
        $this->segments = array_values(array_filter(explode('/', $this->uri)));
    }

    public function getUri() {
        return $this->uri;
    }

    public function segment($index)
	{
		return isset($this->segments[$index - 1]) ? $this->segments[$index - 1] : null;
	}

}