<?php 

function response($data = '', $code = 200) {
    http_response_code($code);
    echo $data;
    die();
}

function jsonResponse($data = '', $code = 200) {
    http_response_code($code);
    json_encode($data);
    die();
}