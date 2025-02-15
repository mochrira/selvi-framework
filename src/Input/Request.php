<?php 

namespace Selvi\Input;

use Selvi\Routing\Route;

class Request {

    private $_method = null;
    private $_post = null;
    private $_get = null;
    private $_raw = null;
    private ?Route $_route = null;

    function setRoute(Route $route): void {
        $this->_route = $route;
    }

    function route(): Route {
        return $this->_route;
    }

    function method() {
        if(!$this->_method) $this->_method = $_SERVER['REQUEST_METHOD'];
        return $this->_method;
    }

    function header($name = null) {
        $headers = apache_request_headers();
		if($name==null){
			return $headers;
		}else{
			if(isset($headers[$name])) {
				return $headers[$name];
			}
			return null;
		}
    }

    function file($name) {
        return (isset($_FILES[$name]) ? $_FILES[$name] : null);
    }

    function post($name) {
        if(!$this->_post) $this->_post = $_POST;
        return $this->_post[$name] ?? null;
    }

    function get($name) {
        if(!$this->_get) $this->_get = $_GET;
        return $this->_get[$name] ?? null;
    }

    function raw() {
        if(!$this->_raw) $this->_raw = file_get_contents('php://input');
        return $this->_raw ?? null;
    }

    function cookie($name) {
        return $_COOKIE[$name];
    }

}