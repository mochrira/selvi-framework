<?php 

declare(strict_types=1);

namespace Selvi\Input;

class Uri {

    private string $baseUrl;
    private string $currentUrl;

    private string $uriString;
    private array $uriSegments;

    public function __construct() {
        $baseUrl = sprintf("%s://%s",
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
            $_SERVER['SERVER_NAME'] . ( $_SERVER['SERVER_PORT'] != '80' ? ':' . $_SERVER['SERVER_PORT'] : '' )
        );

        $this->currentUrl = $baseUrl . $_SERVER['REQUEST_URI'];
        $subDir = dirname($_SERVER['SCRIPT_NAME']);
        $this->baseUrl = $baseUrl . $subDir;

        $uriString = preg_replace('/'.preg_quote($subDir, '/').'/', '', $_SERVER['REQUEST_URI'], 1);
        $parts = explode('?', $uriString);

        $this->uriString = $this->validateUri($parts[0]);
        $this->uriSegments = $this->parseUri($this->uriString);
    }

    private function parseUri(string $uri): array {
        $segments = explode('/', $uri);
        return array_reduce($segments, function ($carry, $item) {
            if(strlen($item) > 0) $carry[] = $item;
            return $carry;
        }, []);
    }

    private function validateUri(string $uri): string {
        $segments = $this->parseUri($uri);
        return '/'.implode('/', $segments);
    }

    public function baseUrl(): string {
        return $this->baseUrl;
    }

    public function currentUrl(): string {
        return $this->currentUrl;
    }

    public function siteUrl(string $uri): string {
        return $this->baseUrl.$this->validateUri($uri);
    }

    public function string(): string {
        return '/'.implode('/', $this->uriSegments);
    }

    public function segments(): array {
        return $this->uriSegments;
    }

    public function segment(int $index): ?string {
        return $this->uriSegments[$index - 1] ?? null;
    }

}