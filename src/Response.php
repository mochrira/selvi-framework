<?php 

namespace Selvi;

class Response {

    private $content;
    private $code;

    function __construct($content = '', $code = 200) {
        $this->content = $content;
        $this->code = $code;
    }

    function send() {
        http_response_code($this->code);
        echo $this->content;
        die();
    }

}