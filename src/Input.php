<?php 

namespace Selvi;

class Input {
	
	function post($name, $filter = FILTER_DEFAULT) : mixed
	{
		if(isset($_POST[$name])){
			if(is_array($_POST[$name])){
				return filter_input(INPUT_POST,$name,$filter,FILTER_REQUIRE_ARRAY);
			}else{
				return filter_input(INPUT_POST,$name,$filter);			
			}
		}else{
			return NULL;
		}
	}

	function file($name, $filter = FILTER_DEFAULT)
	{
		return (isset($_FILES[$name])?$_FILES[$name]:false);
	}
	
	function get($name, $filter = FILTER_DEFAULT)
	{
		if(isset($_GET[$name])){
			return filter_input(INPUT_GET,$name,$filter);
		}
		return NULL;
	}
	
	function server($name, $filter = FILTER_DEFAULT)
	{
		return filter_input(INPUT_SERVER,$name,$filter);
	}

	function method() {
		return $this->server('REQUEST_METHOD');
	}

	function raw($format = ''){
		$raw = file_get_contents('php://input');
		if($format == 'json') {
			return json_decode($raw);
		}
		if($format == 'json_assoc') {
			return json_decode($raw, true);
		}
		return $raw;
	}

	function header($name = ''){
		$headers = apache_request_headers();
		if($name==''){
			return $headers;
		}else{
			if(isset($headers[$name])) {
				return $headers[$name];
			}
			return null;
		}
	}
	
}