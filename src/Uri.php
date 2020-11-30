<?php 

namespace Selvi;

class Uri {

    private $uri;
    private $segments;

    public function __construct() {
        $host = $_SERVER['HTTP_HOST'];

        $uri = parse_url('http://dummy'.$_SERVER['REQUEST_URI']);
        $uri = isset($uri['path']) ? $uri['path'] : '';

        if (isset($_SERVER['SCRIPT_NAME'][0]))
        {
            if (strpos($uri, $_SERVER['SCRIPT_NAME']) === 0) {
                $uri = (string) substr($uri, strlen($_SERVER['SCRIPT_NAME']));
            } elseif (strlen(dirname($_SERVER['SCRIPT_NAME'])) > 1 && strpos($uri, dirname($_SERVER['SCRIPT_NAME'])) === 0) {
                $uri = (string) substr($uri, strlen(dirname($_SERVER['SCRIPT_NAME'])));
            }
        }
        
        $has_query = strpos($this->uri, '?');
        if($has_query !== false) {
            $this->uri = substr($this->uri, 0, $has_query);
        }
        $this->segments = array_values(array_filter(explode('/', $this->uri)));
    }

    public function base_url() {
        if (isset($_SERVER['SERVER_ADDR'])){
            if (strpos($_SERVER['SERVER_ADDR'], ':') !== FALSE)
            {
                $server_addr = '['.$_SERVER['SERVER_ADDR'].']';
            }
            else
            {
                $server_addr = $_SERVER['SERVER_ADDR'];
            }

            $base_url = (is_https() ? 'https' : 'http').'://'.$server_addr
                .substr($_SERVER['SCRIPT_NAME'], 0, strpos($_SERVER['SCRIPT_NAME'], basename($_SERVER['SCRIPT_FILENAME'])));
        }
        else
        {
            $base_url = 'http://localhost/';
        }
        return $base_url;
    }

    public function getUri() {
        return $this->uri;
    }

    public function segment($index)
	{
		return isset($this->segments[$index - 1]) ? $this->segments[$index - 1] : null;
	}

}