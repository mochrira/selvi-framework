<?php

require __DIR__.'/../../vendor/autoload.php';

use Selvi\Database\Builder\DDL\AlterBlueprint;
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

// Tabel diberi prefiks demo_ supaya tabel kontak/grup milik tests/app tidak tertimpa.
$buatGrup = function (Blueprint $table) {
    $table->integer('id_grup')->key()->autoIncrement();
    $table->string('nm_grup', 50);
};

$buatKontak = function (Blueprint $table) {
    $table->integer('id_kontak')->key()->autoIncrement();
    $table->string('nm_kontak', 100);
    $table->string('nomor', 20)->nullable();
};

$ubahEmail = function (AlterBlueprint $table) {
    $table->string('email', 100)->nullable()->after('nomor');
    $table->unique(['nomor'], 'uniq_demo_kontak_nomor');
};

$ubahGrup = function (AlterBlueprint $table) {
    $table->integer('id_grup')->nullable()->after('email');
    $table->foreign(['id_grup'], 'demo_grup', ['id_grup'], 'fk_demo_kontak_grup', onDelete: 'SET NULL');
};

Route::get('/', function () use ($buatGrup, $buatKontak, $ubahEmail, $ubahGrup) {
    $schema = DB::schema('main');
    return jsonResponse([
        'create_grup' => $schema->compileCreate('demo_grup', $buatGrup),
        'create_kontak' => $schema->compileCreate('demo_kontak', $buatKontak),
        'alter_email' => $schema->compileAlter('demo_kontak', $ubahEmail),
        'alter_grup' => $schema->compileAlter('demo_kontak', $ubahGrup),
    ]);
});

Route::get('/jalankan', function () use ($buatGrup, $buatKontak, $ubahEmail, $ubahGrup) {
    $schema = DB::schema('main');

    $hasil = [];
    $hasil['drop_kontak'] = $schema->drop('demo_kontak');
    $hasil['drop_grup'] = $schema->drop('demo_grup');
    $hasil['create_grup'] = $schema->create('demo_grup', $buatGrup);
    $hasil['create_kontak'] = $schema->create('demo_kontak', $buatKontak);
    $hasil['alter_email'] = $schema->alter('demo_kontak', $ubahEmail);
    $hasil['alter_grup'] = $schema->alter('demo_kontak', $ubahGrup);

    return jsonResponse($hasil);
});

Route::get('/struktur', function () {
    $db = Manager::get('main');

    $kolom = $db->query('SHOW COLUMNS FROM demo_kontak')->rows();
    $index = $db->query('SHOW INDEX FROM demo_kontak')->rows();

    return jsonResponse([
        'kolom' => array_map(fn($k) => [
            'field' => $k->Field,
            'type' => $k->Type,
            'null' => $k->Null,
            'key' => $k->Key,
            'extra' => $k->Extra,
        ], $kolom),
        'index' => array_values(array_unique(array_map(fn($i) => $i->Key_name, $index))),
    ]);
});

Route::get('/tipe', function () {
    $schema = DB::schema('main');

    return jsonResponse([
        'sql' => $schema->compileCreate('demo_tipe', function (Blueprint $table) {
            $table->integer('a_integer');
            $table->bigInteger('b_bigint');
            $table->string('c_string', 40);
            $table->text('d_text');
            $table->boolean('e_boolean')->default(true);
            $table->decimal('f_decimal', 12, 2)->default(0);
            $table->float('g_float')->nullable();
            $table->date('h_date')->nullable();
            $table->datetime('i_datetime')->nullable();
            $table->string('j_panjang', 10)->length(30);
        }),
        'tanpa_if_not_exists' => $schema->compileCreate('demo_tipe_2', function (Blueprint $table) {
            $table->ifNotExists(false);
            $table->integer('id')->key();
        }),
    ]);
});

Route::get('/sementara', function () {
    $schema = DB::schema('main');

    $hasil = [];
    $hasil['create'] = $schema->create('demo_kontak_temp', function (Blueprint $table) {
        $table->integer('id_kontak')->key()->autoIncrement();
        $table->string('nm_kontak', 100);
        $table->string('nomor', 20)->nullable();
    });

    $hasil['modify'] = $schema->alter('demo_kontak_temp', function (AlterBlueprint $table) {
        $table->string('nm_kontak', 200)->nullable()->change();
    });

    $hasil['add_first'] = $schema->alter('demo_kontak_temp', function (AlterBlueprint $table) {
        $table->integer('id_grup')->nullable()->first();
    });

    $hasil['rename_column'] = $schema->alter('demo_kontak_temp', function (AlterBlueprint $table) {
        $table->renameColumn('nomor', 'telepon');
    });

    $hasil['unique'] = $schema->alter('demo_kontak_temp', function (AlterBlueprint $table) {
        $table->unique(['telepon'], 'uniq_demo_temp_telepon');
    });

    $hasil['drop_index'] = $schema->alter('demo_kontak_temp', function (AlterBlueprint $table) {
        $table->dropIndex('uniq_demo_temp_telepon');
    });

    $hasil['drop_column'] = $schema->alter('demo_kontak_temp', function (AlterBlueprint $table) {
        $table->dropColumn('id_grup');
    });

    $hasil['rename'] = $schema->rename('demo_kontak_temp', 'demo_kontak_temp_2');
    $hasil['truncate'] = $schema->truncate('demo_kontak_temp_2');
    $hasil['drop'] = $schema->drop('demo_kontak_temp_2');

    return jsonResponse($hasil);
});

Route::get('/kunci', function () {
    $schema = DB::schema('main');

    $hasil = [];
    $hasil['create'] = $schema->create('demo_kontak_pk', function (Blueprint $table) {
        $table->integer('a')->nullable();
        $table->integer('b')->nullable();
    });

    $hasil['primary'] = $schema->alter('demo_kontak_pk', function (AlterBlueprint $table) {
        $table->primary(['a', 'b']);
    });

    $hasil['drop_primary'] = $schema->alter('demo_kontak_pk', function (AlterBlueprint $table) {
        $table->dropPrimary();
    });

    $hasil['drop'] = $schema->drop('demo_kontak_pk');

    return jsonResponse($hasil);
});

Route::get('/hapus', function () {
    $schema = DB::schema('main');
    $db = Manager::get('main');

    $hasil = [];
    $adaKontak = $db->query("SHOW TABLES LIKE 'demo_kontak'")->num_rows() > 0;

    $hasil['drop_foreign'] = $adaKontak
        ? $schema->alter('demo_kontak', function (AlterBlueprint $table) {
            $table->dropForeign('fk_demo_kontak_grup');
        })
        : null;

    $hasil['drop_kontak'] = $schema->drop('demo_kontak');
    $hasil['drop_grup'] = $schema->drop('demo_grup');

    return jsonResponse($hasil);
});

\Selvi\Framework::run();
