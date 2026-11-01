<?php

require __DIR__.'/../../vendor/autoload.php';

use Selvi\Database\Builder\DDL\Blueprint;
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
    $schema = DB::schema('main');
    $schema->drop('demo_tulis', true);
    $schema->create('demo_tulis', function (Blueprint $table) {
        $table->integer('id')->key()->autoIncrement();
        $table->string('nama', 50);
        $table->integer('nilai')->nullable();
    });

    $insertId = DB::table('demo_tulis')->insert([
        'nama' => 'A',
        'nilai' => 1,
    ]);

    $multiId = DB::table('demo_tulis')->insert([
        ['nama' => 'B', 'nilai' => 2],
        ['nama' => 'C', 'nilai' => null],
    ]);

    $update = DB::table('demo_tulis')
        ->where([['id', '=', $insertId]])
        ->update(['nama' => 'A diubah', 'nilai' => 10]);

    $delete = DB::table('demo_tulis')
        ->where([['id', '=', $multiId]])
        ->delete();

    $rows = DB::table('demo_tulis')->orderBy('id')->get()->rows();

    $schema->drop('demo_tulis', true);

    return jsonResponse([
        'insert_id' => $insertId,
        'multi_insert_id' => $multiId,
        'update' => $update,
        'delete' => $delete,
        'rows' => $rows,
    ]);
});

Route::get('/tanpa-where', function () {
    try {
        DB::table('demo_tulis')->update(['nama' => 'massal']);
        return jsonResponse(['error' => false]);
    } catch (Throwable $e) {
        return jsonResponse([
            'kelas' => get_class($e),
            'pesan' => $e->getMessage(),
        ], 400);
    }
});

Route::get('/tanpa-where-delete', function () {
    try {
        DB::table('demo_tulis')->delete();
        return jsonResponse(['error' => false]);
    } catch (Throwable $e) {
        return jsonResponse([
            'kelas' => get_class($e),
            'pesan' => $e->getMessage(),
        ], 400);
    }
});

\Selvi\Framework::run();
