<?php

require __DIR__.'/../../vendor/autoload.php';

use Selvi\Database\Builder\DDL\Blueprint;
use Selvi\Database\DB;
use Selvi\Database\Manager;
use Selvi\Database\Schema;
use Selvi\Env;
use Selvi\Exception\Handler;
use Selvi\Routing\Route;

Handler::setDefaultHandlers();
Handler::listen();

Env::load(__DIR__.'/../private/.ENV');

Manager::add('demo_seed', [
    'driver' => Env::get('DB_DRIVER', 'mysql'),
    'host' => Env::get('DB_HOST'),
    'port' => Env::get('DB_PORT'),
    'username' => Env::get('DB_USER'),
    'password' => Env::get('DB_PASS'),
    'database' => Env::get('DB_NAME'),
]);

$file = '20260101_01_demo_seed.php';

Route::get('/siapkan', function () use ($file) {
    $schema = new Schema('demo_seed');
    $schema->drop('demo_seed');
    $schema->create('demo_seed', function (Blueprint $table) {
        $table->integer('id')->key()->autoIncrement();
        $table->string('nama', 50);
        $table->integer('nilai')->nullable();
    });

    DB::table('_migration')
        ->useConnection('demo_seed')
        ->where([['filename', '=', $file]])
        ->delete();

    return jsonResponse(['siap' => true]);
});

Route::get('/', function () use ($file) {
    $rows = DB::table('demo_seed')
        ->useConnection('demo_seed')
        ->orderBy('id')
        ->get()
        ->rows();

    $log = DB::table('_migration')
        ->useConnection('demo_seed')
        ->where([['filename', '=', $file]])
        ->orderBy('start', 'DESC')
        ->limit(1)
        ->get()
        ->row();

    return jsonResponse([
        'jumlah' => count($rows),
        'data' => $rows,
        'log_terakhir' => $log,
    ]);
});

\Selvi\Framework::run();
