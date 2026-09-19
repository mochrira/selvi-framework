<?php

use Selvi\Output\JsonResponse;
use Selvi\Output\Response;

if(!function_exists('response')) {
    function response(?string $content = '', int $code = 200): Response {
        return new Response($content, $code);
    }
}

if(!function_exists('jsonResponse')) {
    function jsonResponse(?array $data = null, int $code = 200, int $options = JSON_PRETTY_PRINT): JsonResponse {
        return new JsonResponse($data, $code, $options);
    }
}