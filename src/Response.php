<?php 

namespace Selvi;

class Response {

    protected $content;
    protected $code;

    public function __construct($content, $code = 200) {
        $this->content = $content;
        $this->code = $code;
    }

    public function setContent($content) {
        $this->content = $content;
    }

    public function getContent() {
        return $content;
    }

    public function setCode($code) {
        $this->code = $code;
    }

    public function getCode() {
        return $this->code;
    }

    public function send() {
        http_response_code($this->code);
        echo $this->content;
        if(php_sapi_name() == 'cli') echo "\n";
        die();
    }

}