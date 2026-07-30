<?php 

declare(strict_types=1);

namespace Selvi;

use Selvi\Output\Response;

class View {

    public static array $paths = [__DIR__.'/views'];

    public static function addPath(string $path): void {
        self::$paths[] = $path;
    }

    private static function getAbsoluteFilePath(string $file): ?string {
        $i = 0;
        while($i <= count(self::$paths) - 1) {
            $path = self::$paths[$i].'/'.$file;
            if(is_file($path)) return $path;
            $i++;
        }
        return null;
    }

    private static array $vars = [];
    private string $file;

    function __construct(string $file) {
        $this->file = self::getAbsoluteFilePath($file);
    }

    public function setVar(string $name, mixed $value): static {
        self::$vars[$name] = $value;
        return $this;
    }

    public function include(): void {
        extract(self::$vars);
        include($this->file);
    }

    public function render(int $code = 200): Response {
        ob_start();
        extract(self::$vars);
        include($this->file);
        $content = ob_get_contents();
        ob_end_clean();
        return new Response($content, $code);
    }

}