<?php

require __DIR__.'/../../vendor/autoload.php';

use Selvi\Database\DB;
use Selvi\Database\Manager;
use Selvi\Env;
use Selvi\Exception\Handler;
use Selvi\Routing\Route;

Handler::setDefaultHandlers();
Handler::listen();

Env::load(__DIR__.'/../private/.ENV');

Manager::add('main', [
    'driver' => Env::get('DB_DRIVER', 'mysql'),
    'host' => Env::get('DB_HOST'),
    'port' => Env::get('DB_PORT'),
    'username' => Env::get('DB_USER'),
    'password' => Env::get('DB_PASS'),
    'database' => Env::get('DB_NAME'),
]);

Route::get('/', function () {
    $rows = DB::table('kontak')
        ->select('id_kontak', 'nm_kontak', 'id_departemen')
        ->orderBy('id_kontak')
        ->limit(3)
        ->get()
        ->rows();

    return jsonResponse(['data' => $rows]);
});

Route::get('/sql', function () {
    $query = DB::table('kontak')
        ->leftJoin('departemen', 'departemen.id_departemen = kontak.id_departemen')
        ->select(
            'kontak.id_departemen AS id_departemen',
            ['COUNT(kontak.id_kontak)', 'jumlah'])
        ->where([['kontak.id_departemen', '!=', null]])
        ->group('kontak.id_departemen')
        ->orderBy('kontak.id_departemen', 'DESC')
        ->limit(2)
        ->offset(0);

    return jsonResponse(['sql' => $query->getSql()]);
});

Route::get('/row', function () {
    $result = DB::table('kontak')
        ->where([['id_kontak', '=', 5]])
        ->get();

    return jsonResponse([
        'num_rows' => $result->num_rows(),
        'row' => $result->row(),
    ]);
});

Route::get('/where', function () {
    $lebihDari = DB::table('kontak')
        ->where([['id_kontak', '>', 6]])
        ->get()
        ->num_rows();

    $null = DB::table('kontak')
        ->where([['id_departemen', '=', null]])
        ->get()
        ->num_rows();

    $raw = DB::table('kontak')
        ->where('nm_kontak LIKE "%John%"')
        ->get()
        ->num_rows();

    $nested = DB::table('kontak')
        ->where(function ($builder) {
            $builder->orWhere([['id_kontak', '=', 5]]);
            $builder->orWhere([['id_kontak', '=', 9]]);
        })
        ->get()
        ->num_rows();

    return jsonResponse([
        'operator' => $lebihDari,
        'null' => $null,
        'raw' => $raw,
        'nested' => $nested,
    ]);
});

Route::get('/join', function () {
    $rows = DB::table('kontak')
        ->leftJoin('departemen', 'departemen.id_departemen = kontak.id_departemen')
        ->select(
            'kontak.id_departemen AS id_departemen',
            'departemen.nm_departemen AS nm_departemen',
            ['COUNT(kontak.id_kontak)', 'jumlah'])
        ->group('kontak.id_departemen', 'departemen.nm_departemen')
        ->orderBy('kontak.id_departemen')
        ->get()
        ->rows();

    return jsonResponse(['data' => $rows]);
});

Route::get('/row-kosong', function () {
    $result = DB::table('kontak')
        ->where([['id_kontak', '=', 999999]])
        ->get();

    return jsonResponse([
        'num_rows' => $result->num_rows(),
        'row' => $result->row(),
    ]);
});

Route::get('/hasil-bool', function () {
    $result = Manager::get('main')->query('SET @demo_selvi = 1');

    return jsonResponse([
        'tipe' => get_debug_type($result),
        'nilai' => $result,
    ]);
});

Route::get('/order-limit', function () {
    $rows = DB::table('kontak')
        ->select('id_kontak')
        ->orderBy('id_kontak', 'DESC')
        ->limit(2)
        ->offset(1)
        ->get()
        ->rows();

    return jsonResponse(['id' => array_column($rows, 'id_kontak')]);
});

\Selvi\Framework::run();
