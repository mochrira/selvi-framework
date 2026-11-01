<?php

require __DIR__.'/../../vendor/autoload.php';

use Selvi\Database\Builder\DML\Clauses\SelectNode;
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

Route::get('/fungsi', function () {
    $rows = DB::table('kontak')
        ->select(
            'id_kontak',
            SelectNode::function('COALESCE', [
                SelectNode::column('nm_kontak'),
                SelectNode::literal('Tanpa Nama'),
            ], 'nama'),
            SelectNode::function('CONCAT', [
                SelectNode::column('id_kontak'),
                SelectNode::literal('#'),
                SelectNode::column('nm_kontak'),
            ], 'label'))
        ->selectFunction('NOW', alias: 'sekarang')
        ->where([['id_kontak', '=', 5]])
        ->get()
        ->rows();

    return jsonResponse(['data' => $rows]);
});

Route::get('/fungsi-agregat', function () {
    $rows = DB::table('kontak')
        ->select('id_departemen')
        ->selectFunction('COUNT', [SelectNode::star()], 'total')
        ->selectFunction('MAX', [SelectNode::column('id_kontak')], 'maks')
        ->group('id_departemen')
        ->get()
        ->rows();

    return jsonResponse(['data' => $rows]);
});

Route::get('/fungsi-bertumpuk', function () {
    $rows = DB::table('kontak')
        ->select(
            'id_kontak',
            SelectNode::function('COALESCE', [
                SelectNode::function('IFNULL', [
                    SelectNode::column('nm_kontak'),
                    SelectNode::literal(''),
                ]),
                SelectNode::literal('Tanpa Nama'),
            ], 'nama'))
        ->where([['id_kontak', '=', 5]])
        ->get()
        ->rows();

    return jsonResponse(['data' => $rows]);
});

Route::get('/fungsi-sql', function () {
    $sql = DB::table('kontak')
        ->select('id_kontak')
        ->selectFunction('COALESCE', [
            SelectNode::column('nm_kontak'),
            SelectNode::literal('Tanpa Nama'),
        ], 'nama')
        ->selectFunction('CONCAT', [
            SelectNode::column('id_kontak'),
            SelectNode::literal('#'),
            SelectNode::column('nm_kontak'),
        ], 'label')
        ->getSql();

    return jsonResponse(['sql' => $sql]);
});

Route::get('/fungsi-null', function () {
    $sql = DB::table('kontak')
        ->selectFunction('COALESCE', [
            SelectNode::literal(null),
            SelectNode::column('nm_kontak'),
            SelectNode::literal('Tanpa Nama'),
        ], 'nama')
        ->getSql();

    return jsonResponse(['sql' => $sql]);
});

Route::get('/fungsi-raw', function () {
    $sql = DB::table('kontak')
        ->selectRaw('COUNT(*)', 'total')
        ->getSql();

    return jsonResponse(['sql' => $sql]);
});

Route::get('/fungsi-invalid', function () {
    try {
        DB::table('kontak')->selectFunction('1COALESCE')->getSql();
        return jsonResponse(['error' => false]);
    } catch (Throwable $e) {
        return jsonResponse([
            'kelas' => get_class($e),
            'pesan' => $e->getMessage(),
        ], 400);
    }
});

\Selvi\Framework::run();
