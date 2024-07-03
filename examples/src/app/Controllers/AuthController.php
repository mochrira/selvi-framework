<?php 

namespace App\Controllers;

use App\Middlewares\AuthMiddleware;
use App\Models\Pengguna;
use Selvi\Exception;
use Selvi\Request;

class AuthController {

    function __construct(
        private Pengguna $PenggunaModel
    ) { }

    function getToken(Request $request, AuthMiddleware $auth) {
        $data = json_decode($request->raw(), true);

        $error = [];
        if(!isset($data['username'])) $error['username'] = 'Username harus diisi';
        if(!isset($data['password'])) $error['password'] = 'Password harus diisi';

        $pengguna = null;
        if(isset($data['username']) && isset($data['password'])) {
            $pengguna = $this->PenggunaModel->row([['username', $data['username']]]);
            if(!$pengguna) {
                $error['username'] = 'Username salah';
            } else {
                if($pengguna->password !== md5($data['password'])) $error['password'] = 'Password salah';
            }
        }
        if(count($error) > 0) throw new Exception('Periksa kembali isian anda', 'auth/invalid-input', 400, $error);

        $claims = ['idPengguna' => $pengguna->idPengguna];
        $response = jsonResponse(['token' => $auth->generateToken($claims)]);
        $response->cookie('refresh', $auth->generateRefreshToken($claims));
        return $response;
    }

    function info(AuthMiddleware $auth) {
        $penggunaAktif = (array)$auth->user();
        unset($penggunaAktif['password']);
        return jsonResponse($penggunaAktif);
    }

    function refreshToken(AuthMiddleware $auth) {
        $response = jsonResponse();
        $penggunaAktif = $auth->user();
        $response->setData(['token' => $auth->generateToken([
            'idPengguna' => $penggunaAktif->idPengguna
        ])]);
        return $response;
    }

}