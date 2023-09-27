<?php 

namespace App\Controllers;

use Selvi\Exception;

class AuthController {

    // Simple Response
    function simple() {
        return response('Test', 200);
    }

    // JSON Response
    function json() {
        return jsonResponse([
            'foo' => 'bar'
        ], 200);
    }

    // Exception
    function exception() {
        throw new Exception('Akses gagal', 'auth/invalid-akses', 403);
    }
}