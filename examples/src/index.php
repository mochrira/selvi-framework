<?php 

declare(strict_types=1);

require 'vendor/autoload.php';
define('BASEPATH',__DIR__);

require('app/Config/database.php');

use Selvi\Database\Manager;
use Selvi\Response;
use Selvi\Route;
use Selvi\Framework;

/** Simplest way */
Route::get('/',function() { 
    $db = Manager::get('main');
    $pengguna = $db->query("SELECT * FROM pengguna");
    $data = [
        "nmPengguna" => "Saya",
        "akses" => "ADMIN"
    ];
    // $isUpdate = $db->where([['pengguna.idPengguna', 2]])->update(tbl:"pengguna", data:$data);
    // $isDelete = $db->where([['pengguna.akses', "USER"]])->delete("pengguna");

    return new Response('Index');
});


Framework::run();