<?php

require __DIR__.'/../../vendor/autoload.php';

use Selvi\Database\DB;
use Selvi\Database\Manager;
use Selvi\Database\Schema;
use Selvi\Env;
use Selvi\Exception\Handler;
use Selvi\Routing\Route;

Handler::setDefaultHandlers();
Handler::listen();

Env::load(__DIR__.'/../private/.ENV');

Manager::add('demo_migrasi', [
    'driver' => Env::get('DB_DRIVER', 'mysql'),
    'host' => Env::get('DB_HOST'),
    'port' => Env::get('DB_PORT'),
    'username' => Env::get('DB_USER'),
    'password' => Env::get('DB_PASS'),
    'database' => Env::get('DB_NAME'),
]);

$file = '20260101_01_create_demo_migrasi.php';

Route::get('/', function () use ($file) {
    $tables = Manager::get('demo_migrasi')
        ->query("SHOW TABLES LIKE 'demo_migrasi'")
        ->rows();

    $log = DB::table('_migration')
        ->useConnection('demo_migrasi')
        ->where([['filename', '=', $file]])
        ->orderBy('start', 'DESC')
        ->limit(1)
        ->get()
        ->row();

    return jsonResponse([
        'tabel_ada' => !empty($tables),
        'log_terakhir' => $log,
    ]);
});

Route::get('/bersihkan', function () use ($file) {
    (new Schema('demo_migrasi'))->drop('demo_migrasi');

    DB::table('_migration')
        ->useConnection('demo_migrasi')
        ->where([['filename', '=', $file]])
        ->delete();

    return jsonResponse(['dibersihkan' => true]);
});

\Selvi\Framework::run();
