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
    $db = Manager::get('mysql');
    $db->connect();
    // $db->dropColumn('user')->alter('Pengguna');
    // $pengguna = $db->select([
    //     "count(pengguna.idAkses) as jml",
    //     "akses.nmAkses"
    // ])
    // ->leftjoin("pengguna","pengguna.idAkses = akses.idAkses")
    // ->groupBy(['akses.nmAkses'])
    // ->get("akses")->result();
    // var_dump($pengguna);
    // $data = [
    //     "nmPengguna" => "Saya",
    //     "akses" => "ADMIN"
    // ];
    // $isUpdate = $db->where([['pengguna.idPengguna', 2]])->update(tbl:"pengguna", data:$data);
    // $db->orWhere([['pengguna.idPengguna', 2],['pengguna.nmPengguna' , "admin"]]);
    // $isDelete = $db->where([['pengguna.akses', "USER"]])->delete("pengguna");
    // $modify = $db->modifyColumn("umur","int")->alter("peserta");
    // $add = $db->addColumn("umur","int")->alter("peserta");
    // $drop = $db->dropColumn("umur")->alter("peserta");
    // $add = $db->addPrimary("id, peringkat","PK_Primary")->alter("peserta");
    // $drop = $db->dropPrimary()->alter("peserta");
    // $db->createIndex("pengguna","FK_Pengguna",["idAkses"]);
    // $db->dropIndex("pengguna","FK_Pengguna");

    return new Response('Index');
});

Framework::run();