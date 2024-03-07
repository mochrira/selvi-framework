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
        return $this->content ?? null;
    }

    public function setCode($code) {
        $this->code = $code;
    }

    public function getCode() {
        return $this->code ?? null;
    }

    public function send() {
        if(php_sapi_name() !== 'cli') http_response_code($this->code ?? 200);
        $content = $this->getContent();
        if($content != null) echo $content;
        if(php_sapi_name() == 'cli') echo "\n";
        die();
    }

}