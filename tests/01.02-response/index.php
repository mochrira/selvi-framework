<?php

require __DIR__.'/../../vendor/autoload.php';

use Selvi\Routing\Route;
use Selvi\View;

View::addPath(__DIR__.'/views');

Route::get('/', function () {
    return response('Halo, Selvi Framework!');
});

Route::get('/json', function () {
    return jsonResponse(['pesan' => 'Halo', 'kode' => 200]);
});

Route::get('/tidak-ditemukan', function () {
    return response('Data tidak ditemukan', 404);
});

Route::get('/json-header', function () {
    header('Content-Type: application/json');
    return jsonResponse(['pesan' => 'Halo']);
});

Route::get('/tanpa-isi', function () {
    return jsonResponse(null, 204);
});

Route::get('/halaman', function () {
    return view('home.php', [
        'judul' => 'Selvi Framework',
        'nama' => 'Pengunjung'
    ])->render();
});

Route::get('/cookie-set', function () {
    $res = response('ok');
    $res->cookie('demo', 'abc123');
    return $res;
});

Route::get('/options', function () {
    return jsonResponse(['url' => 'http://contoh.test/a/b'], 200, JSON_UNESCAPED_SLASHES);
});

Route::get('/ubah-respons', function () {
    $res = response('awal');
    $res->setContent('diubah');
    $res->setCode(201);
    return $res;
});

Route::get('/halaman-hilang', function () {
    return view('tidak-ada')->render();
});

Route::get('/tanpa-return', function () {
    echo 'Halo dari echo';
});

\Selvi\Framework::run();
