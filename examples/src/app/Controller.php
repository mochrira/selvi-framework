<?php 

namespace App;

use App\Models\Pengguna;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Selvi\Controller as SelviController;
use Selvi\Exception;
use Selvi\Factory;
use Selvi\Request;

class Controller extends SelviController {

    protected Configuration $tokenConfig;
    protected Configuration $refreshConfig;

    protected Plain $parsedToken;
    protected Plain $parsedRefreshToken;

    protected Pengguna $PenggunaModel;
    protected $penggunaAktif;

    function __construct() { 
        $this->tokenConfig = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText(file_get_contents(BASEPATH.'/../private/.JWT_KEY'))
        );

        $this->refreshConfig = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText(file_get_contents(BASEPATH.'/../private/.REFRESH_KEY'))
        );

        $this->PenggunaModel = Factory::resolve(Pengguna::class);
    }

    function generateToken($claims = []) {
        $now = new \DateTimeImmutable();
        $builder = $this->tokenConfig->builder()
            ->issuedAt($now)
            ->expiresAt($now->modify('+1 minutes'));

        foreach($claims as $key => $value) {
            $builder = $builder->withClaim($key, $value);
        }

        return $builder
            ->getToken($this->tokenConfig->signer(), $this->tokenConfig->signingKey())
            ->toString();
    }

    function generateRefreshToken($claims = []) {
        $now = new \DateTimeImmutable();
        $builder = $this->refreshConfig->builder()
            ->issuedAt($now)
            ->expiresAt($now->modify('+1 minutes'));

        foreach($claims as $key => $value) {
            $builder = $builder->withClaim($key, $value);
        }

        return $builder
            ->getToken($this->refreshConfig->signer(), $this->refreshConfig->signingKey())
            ->toString();
    }

    function validateToken(Request $request) {
        $tokenStr = $request->header('Authorization');
        $this->parsedToken = $this->tokenConfig->parser()->parse($tokenStr);

        if(!$this->tokenConfig->validator()->validate(
            $this->parsedToken, new SignedWith($this->tokenConfig->signer(), $this->tokenConfig->signingKey())
        )) throw new Exception('Token expired', 'auth/invalid-token', 403);

        if(!$this->tokenConfig->validator()->validate(
            $this->parsedToken, new LooseValidAt(SystemClock::fromSystemTimezone())
        )) throw new Exception('Token expired', 'auth/token-expired', 403);

        $idPengguna = $this->parsedToken->claims()->get('idPengguna');
        $this->penggunaAktif = $this->PenggunaModel->row([['idPengguna', $idPengguna]]);
        if(!$this->penggunaAktif) throw new Exception('Invalid pengguna', 'auth/invalid-pengguna', 403);
    }

    function validateRefreshToken(Request $request) {
        $tokenStr = $request->header('Authorization');
        $this->parsedRefreshToken = $this->tokenConfig->parser()->parse($tokenStr);

        if(!$this->tokenConfig->validator()->validate(
            $this->parsedRefreshToken, new SignedWith($this->tokenConfig->signer(), $this->tokenConfig->signingKey())
        )) throw new Exception('Token expired', 'auth/invalid-token', 403);

        if(!$this->tokenConfig->validator()->validate(
            $this->parsedRefreshToken, new LooseValidAt(SystemClock::fromSystemTimezone())
        )) {
            $claims = $this->parsedRefreshToken->claims();
            $now = new \DateTimeImmutable();
            if($now->diff($claims->get('exp'))->i <= 5) {
                throw new Exception('Refresh token need regenerate', 'auth/refresh-token-need-regenerate', 403);
            } else {
                throw new Exception('Token expired', 'auth/token-expired', 403);
            }
        }

        $idPengguna = $this->parsedRefreshToken->claims()->get('idPengguna');
        $this->penggunaAktif = $this->PenggunaModel->row([['idPengguna', $idPengguna]]);
        if(!$this->penggunaAktif) throw new Exception('Invalid pengguna', 'auth/invalid-pengguna', 403);
    }

}