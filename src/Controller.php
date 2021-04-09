<?php 

namespace Selvi;
use Selvi\Factory;

class Controller {

    function __get($name) {
        return Factory::load($name);
    }
    
    protected function load($name, $customName = '', $params = []) {
        Factory::load($name, $params, $customName);
    }

}