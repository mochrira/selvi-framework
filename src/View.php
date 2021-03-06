<?php 

namespace Selvi;
use Selvi\Exception;

class View {

    public static $templateDirs = [];

    public static function setup($templateDir) {
        self::$templateDirs[] = $templateDir;
    }

    public function render($name, $data) {
        $found = false;
        $index = 0;
        while(!$found) {
            $file = self::$templateDirs[$index].'/'.$name.'.php';
            if(!is_file($file)) {
                $index++;
            } else {
                $found = true;
            }
        }

        if(!$found) {
            Throw new Exception('View `'.$name.'` not found', 'view/not-found');
        }

        ob_start();
        extract($data);
        include($file);
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

}