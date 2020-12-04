<?php 

namespace Selvi;
use Selvi\Factory;

class Controller {

    function __get($name) {
        return Factory::load($name);
    }
    
    protected function load($name, $params = [], $customName = '') {
        if(is_string($params)) {
            $customName = $params;
            $params = [];
        }
        Factory::load($name, $params, $customName);
    }

}