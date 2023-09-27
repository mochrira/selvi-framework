<?php 

namespace Selvi;

class JsonResponse extends Response {

    protected $data = null;
    protected $options = null;

    public function __construct($data, $code = 200, $options = JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT) {
        parent::__construct($data, $code);
        $this->data = $data;
        $this->options = $options;
    }

    public function setData($data) {
        $this->data = $data;
    }

    public function getData() {
        return $this->data;
    }

    public function setOptions($options) {
        $this->options = $options;
    }

    public function getOptions() {
        return $this->options;
    }

    public function send() {
        if($this->data != null) $this->setContent(json_encode($this->data, $this->options));
        parent::send();
    }

}