<?php 

namespace Selvi\Output;

class JsonResponse extends Response {

    private $jsonData;
    private $jsonOptions;

    function __construct(array $data = null, int $code = 200, $options = JSON_PRETTY_PRINT) {
        $this->jsonData = $data;
        $this->jsonOptions = $options;
        $this->setCode($code);
    }

    function getData() {
        return $this->jsonData;
    }

    function getOptions() {
        return $this->jsonOptions;
    }

    function setData(array $data) {
        $this->jsonData = $data;
    }

    function setOptions($options) {
        $this->jsonOptions = $options;
    }

    function send() {
        $this->setContent($this->jsonData != null ? json_encode($this->jsonData, $this->jsonOptions) : null);
        parent::send();
    }

}