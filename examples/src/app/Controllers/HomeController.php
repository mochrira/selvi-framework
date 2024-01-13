<?php 

namespace App\Controllers;

use App\Models\HomeModel;
use Selvi\Response;
use Selvi\Uri;
use Selvi\Controller;

class HomeController extends Controller {

    function __construct(
        private Uri $uri
    ) { }

    function index(HomeModel $homeModel) {
        return new Response(json_encode([
            'name' => $homeModel->getName(),
            'baseUrl' => $this->uri->baseUrl(),
            'currentUrl' => $this->uri->currentUrl(),
            'currentUri' => $this->uri->string(),
            'segments' => $this->uri->segments()
        ], JSON_PRETTY_PRINT));
    }
    
}