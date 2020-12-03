<?php

namespace Selvi;

class Factory{
    
    private static $object = array();
    
    public static function load($name, $params = array(), $customName = ''){
        $realName = $name;
        if(strlen($customName) > 0) {
            $realName = $customName;
        }

        if(!isset(self::$object[$realName])){
            self::$object[$realName]=new $name(...$params);
        }
        return self::$object[$realName];
    }
    
}