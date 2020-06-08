<?php 

use Selvi\Response;

function response($data = '', $code = 200) {
    return new Response($data, $code);
}

function jsonResponse($data = '', $code = 200) {
    return new Response(json_encode($data, JSON_PRETTY_PRINT), $code);
}