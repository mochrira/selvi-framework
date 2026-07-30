<?php 

declare(strict_types=1);

namespace Selvi\Input;

use Selvi\Routing\Route;

class Request {

    private ?string $_method = null;
    private ?array $_post = null;
    private ?array $_get = null;
    private ?string $_raw = null;
    private ?Route $_route = null;

    public function setRoute(Route $route): void {
        $this->_route = $route;
    }

    public function route(): Route {
        return $this->_route;
    }

    public function method(): string {
        if(!$this->_method) $this->_method = $_SERVER['REQUEST_METHOD'];
        return $this->_method;
    }

    public function header(?string $name = null): array|string|null {
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

    public function file(string $name): ?array {
        return (isset($_FILES[$name]) ? $_FILES[$name] : null);
    }

    public function post(string $name): mixed {
        if(!$this->_post) $this->_post = $_POST;
        return $this->_post[$name] ?? null;
    }

    public function get(string $name): mixed {
        if(!$this->_get) $this->_get = $_GET;
        return $this->_get[$name] ?? null;
    }

    public function raw(): ?string {
        if(!$this->_raw) $this->_raw = file_get_contents('php://input');
        return $this->_raw ?? null;
    }

    public function cookie(string $name): mixed {
        return $_COOKIE[$name];
    }

}