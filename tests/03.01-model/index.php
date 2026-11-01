<?php

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use Selvi\Database\Builder\DML\Clauses\SelectNode;
use Selvi\Database\Manager;
use Selvi\Env;
use Selvi\Exception\Handler;
use Selvi\Model;
use Selvi\Model\Attributes\Column;
use Selvi\Model\Attributes\NotMapped;
use Selvi\Model\Attributes\Table;
use Selvi\Routing\Route;
use Selvi\Tests\Models\Kontak;

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

#[Table('kontak', 'main')]
class KontakNotMapped extends Model {

    #[Column('id_kontak', key: true)]
    public int $idKontak;

    #[Column('nm_kontak')]
    public ?string $nmKontak;

    #[NotMapped]
    public ?string $catatan;

}


#[Table('kontak', 'main')]
class KontakEkspresi extends Model {

    #[Column('id_kontak', key: true)]
    public int $idKontak;

    #[Column('nm_kontak', expression: 'namaKontak')]
    public ?string $nmKontak;

    public static function namaKontak(array $context): array {
        return SelectNode::function('COALESCE', [
            SelectNode::column($context['column'], $context['source']),
            SelectNode::literal('Tanpa Nama'),
        ]);
    }

}

#[Table('kontak', 'main')]
class KontakEkspresiArray extends Model {

    #[Column('id_kontak', key: true)]
    public int $idKontak;

    #[Column('nm_kontak', expression: [
        'type' => 'function',
        'name' => 'COALESCE',
        'arguments' => [
            ['type' => 'column', 'column' => 'nm_kontak'],
            ['type' => 'literal', 'value' => 'Tanpa Nama'],
        ],
    ])]
    public ?string $nmKontak;

}

#[Table('kontak', 'main')]
class KontakEkspresiKey extends Model {

    #[Column('id_kontak', key: true, expression: 'idKontak')]
    public int $idKontak;

}

#[Table('kontak', 'main')]
class KontakEkspresiRaw extends Model {

    #[Column('id_kontak', key: true)]
    public int $idKontak;

    #[Column('nm_kontak', expression: [
        'type' => 'raw',
        'sql' => "COALESCE(kontak.nm_kontak, 'Tanpa Nama')",
    ])]
    public ?string $nmKontak;

}

#[Table('kontak', 'main')]
class KontakEkspresiResolverMissing extends Model {

    #[Column('id_kontak', key: true)]
    public int $idKontak;

    #[Column('nm_kontak', expression: 'tidakAda')]
    public ?string $nmKontak;

}

#[Table('kontak', 'main')]
class KontakEkspresiResolverNonStatic extends Model {

    #[Column('id_kontak', key: true)]
    public int $idKontak;

    #[Column('nm_kontak', expression: 'namaKontak')]
    public ?string $nmKontak;

    public function namaKontak(array $context): array {
        return SelectNode::literal('x');
    }

}

#[Table('kontak', 'main')]
class KontakEkspresiResolverTipe extends Model {

    #[Column('id_kontak', key: true)]
    public int $idKontak;

    #[Column('nm_kontak', expression: 'resolverTipe')]
    public ?string $nmKontak;

    public static function resolverTipe(array $context): array {
        return ['type' => 'tidak_dikenal'];
    }

}

#[Table('kontak', 'main')]
class KontakEkspresiResolverInvalid extends Model {

    #[Column('id_kontak', key: true)]
    public int $idKontak;

    #[Column('nm_kontak', expression: 'resolverSalah')]
    public ?string $nmKontak;

    public static function resolverSalah(array $context): string {
        return 'bukan node';
    }

}

Route::get('/', function () {
    $data = Kontak::query()
        ->orderBy('id_kontak')
        ->limit(2)
        ->all()
        ->toArray();

    return jsonResponse(['data' => $data]);
});

Route::get('/find', function () {
    $kontak = Kontak::find(5);

    return jsonResponse($kontak?->toArray());
});

Route::get('/first-count', function () {
    $first = Kontak::first();

    return jsonResponse([
        'id' => $first?->idKontak,
        'count' => Kontak::count(),
    ]);
});

Route::get('/where', function () {
    return jsonResponse([
        'count' => Kontak::where([['nm_kontak', 'LIKE', '%John%']])->count(),
    ]);
});

Route::get('/offset', function () {
    $rows = Kontak::offset(1)->limit(2)->all();
    $ids = $rows->map(fn($item) => $item->idKontak)->toArray();

    return jsonResponse(['id' => $ids]);
});

Route::get('/to-array-db', function () {
    $kontak = Kontak::find(5);

    return jsonResponse($kontak?->toArrayDb());
});

Route::get('/notmapped', function () {
    $kontak = KontakNotMapped::find(5);
    $kontak->catatan = 'hanya di memori';

    return jsonResponse([
        'toArray' => $kontak->toArray(),
        'toArrayDb' => $kontak->toArrayDb(),
    ]);
});

Route::get('/create-update-delete', function () {
    $kontak = Kontak::create([
        'nm_kontak' => 'Model Demo',
        'id_departemen' => 1,
        'id_unit' => 1,
        'id_profesi' => 1,
        'created_at' => new DateTime(),
    ]);

    $id = $kontak->idKontak;
    $kontak->nmKontak = 'Model Demo Diubah';
    $updated = $kontak->update();
    $deleted = $kontak->delete();

    return jsonResponse([
        'id' => $id,
        'update' => $updated,
        'delete' => $deleted,
        'sisa' => Kontak::find($id) === null,
    ]);
});


Route::get('/ekspresi', function () {
    $kontak = KontakEkspresi::find(5);

    return jsonResponse([
        'data' => $kontak?->toArray(),
        'sql' => KontakEkspresi::query()->build()->getSql(),
    ]);
});

Route::get('/ekspresi-array', function () {
    $kontak = KontakEkspresiArray::find(5);

    return jsonResponse([
        'data' => $kontak?->toArray(),
        'sql' => KontakEkspresiArray::query()->build()->getSql(),
    ]);
});

Route::get('/ekspresi-raw-model', function () {
    $kontak = KontakEkspresiRaw::find(5);

    return jsonResponse([
        'data' => $kontak?->toArray(),
        'sql' => KontakEkspresiRaw::query()->build()->getSql(),
    ]);
});

Route::get('/ekspresi-resolver-missing', function () {
    try {
        KontakEkspresiResolverMissing::find(5);
        return jsonResponse(['error' => false]);
    } catch (Throwable $e) {
        return jsonResponse([
            'kelas' => get_class($e),
            'pesan' => $e->getMessage(),
        ], 400);
    }
});

Route::get('/ekspresi-resolver-nonstatic', function () {
    try {
        KontakEkspresiResolverNonStatic::find(5);
        return jsonResponse(['error' => false]);
    } catch (Throwable $e) {
        return jsonResponse([
            'kelas' => get_class($e),
            'pesan' => $e->getMessage(),
        ], 400);
    }
});

Route::get('/ekspresi-resolver-tipe', function () {
    try {
        KontakEkspresiResolverTipe::find(5);
        return jsonResponse(['error' => false]);
    } catch (Throwable $e) {
        return jsonResponse([
            'kelas' => get_class($e),
            'pesan' => $e->getMessage(),
        ], 400);
    }
});

Route::get('/ekspresi-resolver-invalid', function () {
    try {
        KontakEkspresiResolverInvalid::find(5);
        return jsonResponse(['error' => false]);
    } catch (Throwable $e) {
        return jsonResponse([
            'kelas' => get_class($e),
            'pesan' => $e->getMessage(),
        ], 400);
    }
});

Route::get('/ekspresi-count', function () {
    return jsonResponse(['count' => KontakEkspresi::count()]);
});

Route::get('/ekspresi-toarraydb', function () {
    return jsonResponse(KontakEkspresi::find(5)?->toArrayDb());
});

Route::get('/ekspresi-key', function () {
    try {
        KontakEkspresiKey::find(5);
        return jsonResponse(['error' => false]);
    } catch (Throwable $e) {
        return jsonResponse([
            'kelas' => get_class($e),
            'pesan' => $e->getMessage(),
        ], 400);
    }
});

\Selvi\Framework::run();
