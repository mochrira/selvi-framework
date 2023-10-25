<?php 

namespace App\Controllers;

use Selvi\Controller;

class HomeController extends Controller {

    function index() {
        return response('Halo');
    }
    
}