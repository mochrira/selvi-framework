<?php 

namespace App\Middlewares;

use Selvi\Exception;
use Selvi\Input\Request;
use Selvi\Output\Response;

use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;

use App\Models\PenggunaModel;

class AuthMiddleware {

    private Configuration $tokenConfig;
    private Configuration $refreshConfig;
    private mixed $penggunaAktif;

    function __construct(
        private PenggunaModel $PenggunaModel
    ) {
        $this->tokenConfig = Configuration::forSymmetricSigner(new Sha256(), 
            InMemory::plainText(file_get_contents(BASEPATH.'/../private/.JWT_KEY')));
        $this->refreshConfig = Configuration::forSymmetricSigner(new Sha256(), 
            InMemory::plainText(file_get_contents(BASEPATH.'/../private/.REFRESH_KEY')));
    }

    function user() {
        return $this->penggunaAktif;
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

    function validateToken(Request $request, Callable $next) {
        $tokenStr = $request->header('Authorization') ?? $request->header('authorization');
        /** @var Plain $token */
        $token = $this->tokenConfig->parser()->parse($tokenStr);

        if(!$this->tokenConfig->validator()->validate(
            $token, new SignedWith($this->tokenConfig->signer(), $this->tokenConfig->signingKey())
        )) throw new Exception('Token expired', 'auth/invalid-token', 403);

        if(!$this->tokenConfig->validator()->validate(
            $token, new LooseValidAt(SystemClock::fromSystemTimezone())
        )) throw new Exception('Token expired', 'auth/token-expired', 403);

        $idPengguna = $token->claims()->get('idPengguna');
        $this->penggunaAktif = $this->PenggunaModel->row([['idPengguna', $idPengguna]]);
        if(!$this->penggunaAktif) throw new Exception('Invalid pengguna', 'auth/invalid-pengguna', 403);
        return $next();
    }

    function validateRefreshToken(Request $request, Callable $next) {
        $tokenStr = $request->cookie('refresh');
        /** @var Plain $token */
        $token = $this->refreshConfig->parser()->parse($tokenStr);
        if(!$this->refreshConfig->validator()->validate(
            $token, new SignedWith($this->refreshConfig->signer(), $this->refreshConfig->signingKey())
        )) throw new Exception('Token expired', 'auth/invalid-token', 403);

        $needRegenerate = false;
        if(!$this->refreshConfig->validator()->validate(
            $token, new LooseValidAt(SystemClock::fromSystemTimezone())
        )) {
            $claims = $token->claims();
            $now = new \DateTimeImmutable();
            if($now->diff($claims->get('exp'))->i <= 1) {
                $needRegenerate = true;
            } else {
                throw new Exception('Token expired', 'auth/token-expired', 403);
            }
        }

        $idPengguna = $token->claims()->get('idPengguna');
        $this->penggunaAktif = $this->PenggunaModel->row([['idPengguna', $idPengguna]]);
        if(!$this->penggunaAktif) throw new Exception('Invalid pengguna', 'auth/invalid-pengguna', 403);

        if($needRegenerate == true) {
            /** @var Response $response */
            $response = $next();
            $response->cookie('refresh', $this->generateRefreshToken(['idPengguna' => $claims->get('idPengguna')]));
            return $response;
        }

        return $next();
    }

}