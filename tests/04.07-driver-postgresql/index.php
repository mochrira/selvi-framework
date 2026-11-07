<?php

require __DIR__.'/../../vendor/autoload.php';

use Selvi\Database\Builder\DDL\AlterBlueprint;
use Selvi\Database\Builder\DDL\Blueprint;
use Selvi\Database\Builder\DML\Clauses\SelectNode;
use Selvi\Database\DB;
use Selvi\Database\Manager;
use Selvi\Database\Schema;
use Selvi\Env;
use Selvi\Exception\Handler;
use Selvi\Routing\Route;
use Selvi\Tests\Models\DemoPg;

Handler::setDefaultHandlers();
Handler::listen();

Env::load(__DIR__.'/../private/.ENV');

Manager::add('pgdemo', [
    'driver' => 'pgsql',
    'host' => Env::get('DB_PGSQL_HOST'),
    'port' => Env::get('DB_PGSQL_PORT', '5432'),
    'username' => Env::get('DB_PGSQL_USER'),
    'password' => Env::get('DB_PGSQL_PASS'),
    'database' => Env::get('DB_PGSQL_NAME'),
]);

$buatGrup = function (Blueprint $table) {
    $table->integer('id_grup')->key()->autoIncrement();
    $table->string('nm_grup', 50);
};

$buatKontak = function (Blueprint $table) {
    $table->integer('id_kontak')->key()->autoIncrement();
    $table->string('nm_kontak', 150);
    $table->string('nomor', 20)->nullable()->unique();
    $table->boolean('aktif')->default(true);
    $table->decimal('skor', 12, 2)->default(0);
    $table->float('rasio')->nullable();
    $table->date('tanggal')->nullable();
    $table->time('jam')->nullable();
    $table->datetime('dibuat')->nullable();
    $table->text('catatan')->nullable();
    $table->bigInteger('nomor_besar')->nullable();
    $table->integer('id_grup')->nullable();
};

Route::get('/', function () {
    $config = Manager::get('pgdemo')->getConfig();
    $ok = Manager::get('pgdemo')->query('SELECT 1 AS ok');

    return jsonResponse([
        'ekstensi_pgsql' => extension_loaded('pgsql'),
        'driver' => $config['driver'] ?? null,
        'host' => $config['host'] ?? null,
        'database' => $config['database'] ?? null,
        'ok' => (int) $ok->row()->ok,
        'versi' => Manager::get('pgdemo')->query('SHOW server_version')->row()->server_version,
    ]);
});

Route::get('/sql', function () use ($buatGrup, $buatKontak) {
    $schema = DB::schema('pgdemo');

    return jsonResponse([
        'create_grup' => $schema->compileCreate('demo_pgsql_grup', $buatGrup),
        'create_kontak' => $schema->compileCreate('demo_pgsql', $buatKontak),
        'alter_fk' => $schema->compileAlter('demo_pgsql', function (AlterBlueprint $table) {
            $table->foreign(['id_grup'], 'demo_pgsql_grup', ['id_grup'], 'fk_demo_pgsql_grup', onDelete: 'SET NULL');
        }),
        'truncate' => $schema->compileTruncate('demo_pgsql'),
    ]);
});

Route::get('/skema', function () use ($buatGrup, $buatKontak) {
    $schema = new Schema('pgdemo');

    $hasil = [];
    $hasil['drop_kontak'] = $schema->drop('demo_pgsql');
    $hasil['drop_grup'] = $schema->drop('demo_pgsql_grup');
    $hasil['create_grup'] = $schema->create('demo_pgsql_grup', $buatGrup);
    $hasil['create_kontak'] = $schema->create('demo_pgsql', $buatKontak);
    $hasil['alter_fk'] = $schema->alter('demo_pgsql', function (AlterBlueprint $table) {
        $table->foreign(['id_grup'], 'demo_pgsql_grup', ['id_grup'], 'fk_demo_pgsql_grup', onDelete: 'SET NULL');
    });

    $hasil['kolom'] = Manager::get('pgdemo')->query(<<<'SQL'
        SELECT column_name, data_type, is_nullable, column_default, is_identity
        FROM information_schema.columns
        WHERE table_schema = 'public' AND table_name = 'demo_pgsql'
        ORDER BY ordinal_position
    SQL)->rows();

    return jsonResponse($hasil);
});

Route::get('/tulis', function () {
    $db = DB::table('demo_pgsql')->useConnection('pgdemo');

    $rows = [
        ['nm_kontak' => 'Andi', 'nomor' => '001', 'aktif' => true, 'skor' => 12.5, 'catatan' => "kutip ' dan backslash \\"],
        ['nm_kontak' => 'Budi', 'nomor' => '002', 'aktif' => false, 'skor' => 0, 'id_grup' => 1],
        ['nm_kontak' => 'Citra', 'nomor' => '003', 'aktif' => true, 'skor' => -3.25, 'rasio' => 0.5],
    ];

    DB::table('demo_pgsql_grup')->useConnection('pgdemo')->insert(['nm_grup' => 'Umum']);

    $lastIds = [];
    foreach($rows as $row) {
        $lastIds[] = $db->insert($row);
    }

    return jsonResponse([
        'last_id' => $lastIds,
        'rows' => $db->orderBy('id_kontak')->get()->rows(),
    ]);
});

Route::get('/baca', function () {
    $db = DB::table('demo_pgsql')->useConnection('pgdemo');
    $result = $db->orderBy('id_kontak')->get();

    return jsonResponse([
        'num_rows' => $result->num_rows(),
        'row_pertama' => $result->row(),
        'aktif' => array_map(fn($row) => [
            'id' => $row->id_kontak,
            'nilai' => $row->aktif,
            'tipe' => gettype($row->aktif),
        ], $result->rows()),
        'agregat' => DB::table('demo_pgsql')->useConnection('pgdemo')
            ->selectFunction('COUNT', [SelectNode::column('id_kontak')], 'jumlah')
            ->get()->row(),
        'satu_baris' => DB::table('demo_pgsql')->useConnection('pgdemo')
            ->where([['nm_kontak', '=', 'Andi']])
            ->get()->row(),
    ]);
});

Route::get('/model', function () {
    $baru = DemoPg::create(['nm_kontak' => 'Model', 'aktif' => true]);
    $ditemukan = DemoPg::find($baru->idKontak);

    $nonaktif = DemoPg::create(['nm_kontak' => 'Model Nonaktif', 'aktif' => false]);
    $nonaktifDitemukan = DemoPg::find($nonaktif->idKontak);

    return jsonResponse([
        'id' => $baru->idKontak,
        'aktif' => $ditemukan?->aktif,
        'tipe' => gettype($ditemukan?->aktif),
        'nm' => $ditemukan?->nmKontak,
        'aktif_false' => $nonaktifDitemukan?->aktif,
        'tipe_false' => gettype($nonaktifDitemukan?->aktif),
    ]);
});

Route::get('/transaksi', function () {
    $db = Manager::get('pgdemo');

    $db->startTransaction();
    DB::table('demo_pgsql')->useConnection('pgdemo')->insert(['nm_kontak' => 'Rollback']);
    $db->rollback();

    $db->startTransaction();
    DB::table('demo_pgsql')->useConnection('pgdemo')->insert(['nm_kontak' => 'Commit']);
    $db->commit();

    return jsonResponse([
        'setelah_rollback' => DB::table('demo_pgsql')->useConnection('pgdemo')
            ->where([['nm_kontak', '=', 'Rollback']])->get()->num_rows(),
        'setelah_commit' => DB::table('demo_pgsql')->useConnection('pgdemo')
            ->where([['nm_kontak', '=', 'Commit']])->get()->num_rows(),
    ]);
});

Route::get('/createdb', function () {
    $db = Manager::get('pgdemo');
    $nama = 'selvi_test_createdb';

    $hasil = [
        'pertama' => $db->createDatabase($nama),
        'kedua' => $db->createDatabase($nama),
    ];

    $berhasilDrop = (bool) $db->query('DROP DATABASE IF EXISTS ' . $nama);
    $hasil['drop'] = $berhasilDrop;

    return jsonResponse($hasil);
});

Route::get('/error', function () {
    try {
        Manager::get('pgdemo')->query('SELECT * FROM tabel_tidak_ada');
    } catch(\Selvi\Exception\DatabaseException $e) {
        return jsonResponse([
            'state' => $e->getState(),
            'sql' => $e->getSql(),
            'message' => $e->getMessage(),
        ], 500);
    }

    return jsonResponse(['state' => null]);
});

Route::get('/ubah', function () {
    $schema = new Schema('pgdemo');

    $hasil = [];
    $hasil['update'] = DB::table('demo_pgsql')->useConnection('pgdemo')
        ->where([['id_kontak', '=', 1]])
        ->update(['aktif' => false, 'catatan' => "diubah ' \\ % _"]);

    $hasil['alter'] = $schema->alter('demo_pgsql', function (AlterBlueprint $table) {
        $table->integer('umur')->nullable()->after('nm_kontak');
        $table->string('nm_kontak', 200)->nullable()->change();
        $table->renameColumn('nomor', 'telepon');
        $table->index(['telepon'], 'idx_demo_pgsql_telepon');
        $table->unique(['rasio'], 'uniq_demo_pgsql_rasio');
    });

    $hasil['drop_index'] = $schema->alter('demo_pgsql', function (AlterBlueprint $table) {
        $table->dropIndex('idx_demo_pgsql_telepon');
    });

    $hasil['drop_column'] = $schema->alter('demo_pgsql', function (AlterBlueprint $table) {
        $table->dropColumn('umur');
    });

    $hasil['row'] = DB::table('demo_pgsql')->useConnection('pgdemo')
        ->where([['id_kontak', '=', 1]])
        ->get()->row();

    return jsonResponse($hasil);
});

Route::get('/kunci', function () {
    $schema = new Schema('pgdemo');

    $hasil = [];
    $hasil['create'] = $schema->create('demo_pgsql_pk', function (Blueprint $table) {
        $table->integer('a')->nullable();
        $table->integer('b')->nullable();
    });
    $hasil['primary'] = $schema->alter('demo_pgsql_pk', function (AlterBlueprint $table) {
        $table->primary(['a', 'b']);
    });
    $hasil['drop_primary'] = $schema->alter('demo_pgsql_pk', function (AlterBlueprint $table) {
        $table->dropPrimary();
    });
    $hasil['drop'] = $schema->drop('demo_pgsql_pk');

    return jsonResponse($hasil);
});

Route::get('/hapus', function () {
    $schema = new Schema('pgdemo');
    $db = DB::table('demo_pgsql')->useConnection('pgdemo');

    $hasil = [];
    $hasil['delete'] = $db->where([['id_kontak', '=', 2]])->delete();
    $hasil['truncate'] = $schema->truncate('demo_pgsql');
    $hasil['insert_setelah_truncate'] = $db->insert(['nm_kontak' => 'Setelah truncate']);
    $hasil['jumlah'] = DB::table('demo_pgsql')->useConnection('pgdemo')->get()->num_rows();
    $hasil['drop_kontak'] = $schema->drop('demo_pgsql');
    $hasil['drop_grup'] = $schema->drop('demo_pgsql_grup');

    return jsonResponse($hasil);
});

Route::get('/migrasi', function () {
    $db = Manager::get('pgdemo');

    return jsonResponse([
        'tabel_ada' => $db->query("SELECT to_regclass('public.demo_pgsql_migrasi') AS tabel")->row()->tabel !== null,
        'log' => DB::table('_migration')->useConnection('pgdemo')
            ->where([['filename', '=', '20260101_01_create_demo_pgsql.php']])
            ->orderBy('start', 'DESC')
            ->limit(1)
            ->offset(0)
            ->get()
            ->row(),
    ]);
});

Route::get('/bersihkan', function () {
    $schema = new Schema('pgdemo');

    $hasil = [];
    $hasil['drop_kontak'] = $schema->drop('demo_pgsql');
    $hasil['drop_grup'] = $schema->drop('demo_pgsql_grup');

    try {
        $hasil['hapus_log'] = DB::table('_migration')->useConnection('pgdemo')
            ->where([['filename', '=', '20260101_01_create_demo_pgsql.php']])
            ->delete();
    } catch(\Throwable $e) {
        $hasil['hapus_log'] = $e->getMessage();
    }

    return jsonResponse($hasil);
});

\Selvi\Framework::run();
