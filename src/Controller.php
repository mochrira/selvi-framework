<?php 

namespace Selvi;
use Selvi\Factory;
use Selvi\Input;

class Controller {

    function __construct() {
        $this->load(Input::class, 'input');
    }

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