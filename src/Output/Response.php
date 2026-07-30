<?php 

declare(strict_types=1);

namespace Selvi\Output;

class Response {

    protected ?string $content = null;
    protected int $code;

    function __construct(?string $content = '', int $code = 200) {
        $this->content = $content;
        $this->code = $code;
    }

    public function getContent(): ?string {
        return $this->content;
    }

    public function getCode(): int {
        return $this->code;
    }

    public function setContent(?string $content): void {
        $this->content = $content;
    }

    public function setCode(int $code): void {
        $this->code = $code;
    }

    public function cookie(string $name, string $value = "", int $expire = 0, string $path = "", string $domain = "", bool $secure = false, bool $httponly = false): void {
        setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }

    public function send(): void {
        if(php_sapi_name() != 'cli') http_response_code($this->code);
        echo $this->content;
        if(php_sapi_name() == 'cli') echo "\n";
        die();
    }

}