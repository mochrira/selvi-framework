<?php

namespace Selvi;

class Factory{
    
    private static $objects = [];
    
    public static function load($name, $params = [], $customName = ''){
        $realName = $name;
        if(is_string($params)) $customName = $params;
        if(strlen($customName) > 0) $realName = $customName;
        if(!isset(self::$objects[$realName])) 
            self::$objects[$realName]=new $name(...(is_array($params) ? $params : []));
        return self::$objects[$realName];
    }
    
}