<?php 

namespace Selvi;

class Response {

    protected $content;
    protected $code;

    function __construct($content = '', $code = 200) {
        $this->content = $content;
        $this->code = $code;
    }

    function getContent() {
        return $this->content;
    }

    function getCode() {
        return $this->code;
    }

    function setContent($content) {
        $this->content = $content;
    }

    function setCode($code) {
        $this->code = $code;
    }

    function cookie($name, $value = "", $expire = 0, $path = "", $domain = "", $secure = false, $httponly = false) {
        setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }

    function send() {
        if(php_sapi_name() != 'cli') http_response_code($this->code);
        echo $this->content;
        if(php_sapi_name() == 'cli') echo "\n";
        die();
    }

}