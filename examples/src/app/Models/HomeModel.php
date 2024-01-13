<?php 

namespace App\Models;

use Selvi\Model;
use Selvi\Request;

class HomeModel extends Model {

    function __construct(
        private Request $request
    ) { }

    function getName() {
        return 'Rizal';
    }

}