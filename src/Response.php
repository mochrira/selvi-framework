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
        if(php_sapi_name() != 'cli') http_response_code($this->code);
        echo $this->content;
        if(php_sapi_name() == 'cli') echo "\n";
        die();
    }

}