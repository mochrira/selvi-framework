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
    $config = Manager::get('main')->getConfig();
    return jsonResponse([
        'terdaftar' => Manager::has('main'),
        'driver' => $config['driver'] ?? null,
        'host' => $config['host'],
        'database' => $config['database'],
    ]);
});

Route::get('/ping', function () {
    $result = Manager::get('main')->query('SELECT 1 AS ok');
    return jsonResponse(['ok' => (int) $result->row()->ok]);
});

Route::get('/sql', function () {
    return jsonResponse([
        'sql' => DB::table('kontak')
            ->select('id_kontak', 'nm_kontak')
            ->where([['id_kontak', '=', 1]])
            ->getSql(),
    ]);
});

Route::get('/transaksi', function () {
    $db = Manager::get('main');
    return jsonResponse([
        'start' => $db->startTransaction(),
        'rollback' => $db->rollback(),
    ]);
});

Route::get('/tidak-terdaftar', function () {
    try {
        Manager::get('lain');
    } catch (InvalidArgumentException $e) {
        return jsonResponse([
            'exception' => $e::class,
            'message' => $e->getMessage(),
        ]);
    }

    return jsonResponse(['exception' => null]);
});

Route::get('/error', function () {
    // Sengaja gagal: tabel ini tidak ada, untuk melihat bentuk DatabaseException.
    Manager::get('main')->query('SELECT * FROM tabel_yang_tidak_ada');
    return jsonResponse(['ok' => true]);
});

\Selvi\Framework::run();
