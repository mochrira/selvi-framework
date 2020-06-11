<?php 

namespace Selvi;
use Selvi\Exception;

class View {

    public static $templateDir;

    public static function setup($templateDir) {
        self::$templateDir = $templateDir;
    }

    public function render($name, $data) {
        $file = self::$templateDir.'/'.$name.'.php';
        if(!is_file($file)) {
            $file = self::$templateDir.'/'.$name;
            if(!is_file($file)) {
                Throw new Exception('View `'.$name.'` not found', 'view/not-found');
            }
        }

        ob_start();
        extract($data);
        include($file);
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

}