<?php 

namespace Selvi;

class Response {

    private $content;
    private $code;

    public function __construct($content, $code) {
        $this->content = $content;
        $this->code = $code;
    }

    public function send() {
        http_response_code($this->code);
        echo $this->content;
        if(php_sapi_name() == 'cli') {
            echo "\n";
        }
        die();
    }

}