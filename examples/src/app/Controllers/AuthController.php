<?php 

namespace App\Controllers;

use App\Controller;
use Selvi\Exception;
use Selvi\Request;

class AuthController extends Controller {

    function __construct() { 
        parent::__construct();
    }

    function getToken(Request $request) {
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
        $response = jsonResponse(['token' => $this->generateToken($claims)]);
        $response->cookie('refresh', $this->generateRefreshToken($claims));
        return $response;
    }

    function info(Request $request) {
        $this->validateToken($request);
        $pengguna = (array)$this->penggunaAktif;
        unset($pengguna['password']);
        return jsonResponse($pengguna);
    }

    function refreshToken(Request $request) {
        $response = jsonResponse();
        try {
            $this->validateRefreshToken($request);
        } catch(Exception $e) {
            if($e->getCodeString()  == 'auth/refresh-token-need-regenerate') {
                $response->cookie('refresh', $this->generateRefreshToken([
                    'idPengguna' => $this->parsedRefreshToken->claims()->get('idPengguna')
                ]));
            } else {
                throw $e;
            }
        }
        $response->setData(['token' => $this->generateToken([
            'idPengguna' => $this->parsedRefreshToken->claims()->get('idPengguna')
        ])]);
        return $response;
    }

}