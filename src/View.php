<?php 

namespace Selvi;

use Selvi\Response;

class View {

    static $paths = [__DIR__.'/views'];

    static function addPath($path) {
        self::$paths[] = $path;
    }

    private static function getAbsoluteFilePath($file) {
        $i = 0;
        while($i <= count(self::$paths) - 1) {
            $path = self::$paths[$i].'/'.$file;
            if(is_file($path)) return $path;
            $i++;
        }
        return null;
    }

    private static $vars = [];
    private $file;

    function __construct($file) {
        $this->file = self::getAbsoluteFilePath($file);
    }

    function setVar($name, $value) {
        self::$vars[$name] = $value;
        return $this;
    }

    function include() {
        extract(self::$vars);
        include($this->file);
    }

    function render($code = 200) {
        ob_start();
        extract(self::$vars);
        include($this->file);
        $content = ob_get_contents();
        ob_end_clean();
        return new Response($content, $code);
    }

}