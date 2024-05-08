<?php 

try {
    require '../vendor/autoload.php';
    define('BASEPATH', __DIR__);
    require './Config/database.php';
    require '../app/Config/routes.php';
    Selvi\Framework::run();
} catch(Selvi\Exception\HttpException $e) {
    jsonResponse([
        'code' => $e->getCodeString(),
        'message' => $e->getMessage(),
        'uri' => $e->getUri(),
        'method' => $e->getMethod()
    ], $e->getCode())->send();
} catch(Selvi\Exception\DatabaseException $e) {
    jsonResponse([
        'code' => $e->getCodeString(),
        'message' => $e->getMessage(),
        'query' => $e->getSql()
    ], $e->getCode())->send();
} catch(Selvi\Exception $e) {
    jsonResponse([
        'code' => $e->getCodeString(),
        'message' => $e->getMessage()
    ], $e->getCode())->send();
}