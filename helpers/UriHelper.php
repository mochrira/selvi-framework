<?php 

use Selvi\Factory;
use Selvi\Uri;

if(!function_exists('base_url')) {

    function base_url() {
        $uri = Factory::load(Uri::class, 'uri');
        return $uri->base_url();
    }
    
}
