<?php 

namespace Selvi;

use Selvi\Factory;

class Base {

    function __get($name) {
        return Factory::get($name);
    }

    function load($className, $alias) {
        return Factory::load($className, $alias);
    }

}