<?php

require __DIR__.'/../../vendor/autoload.php';

use Selvi\Input\Request;
use Selvi\Routing\Route;

Route::get('/halo', function (Request $request) {
    $nama = $request->get('nama') ?? 'Pengunjung';
    return response("Halo, {$nama}!");
});

Route::post('/kontak', function (Request $request) {
    $data = json_decode($request->raw() ?? '', true) ?? [];
    return jsonResponse(['diterima' => $data], 201);
});

Route::post('/form', function (Request $request) {
    return response('Halo, '.$request->post('nama'));
});

Route::get('/header', function (Request $request) {
    return response($request->header('Authorization') ?? 'tidak ada');
});

Route::get('/token', function (Request $request) {
    return response($request->header('X-Token') ?? 'tidak ada');
});

Route::get('/metode', function (Request $request) {
    return response($request->method());
});

Route::get('/cookie', function (Request $request) {
    return response($request->cookie('sesi') ?? 'tidak ada');
});

\Selvi\Framework::run();
