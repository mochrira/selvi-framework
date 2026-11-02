<?php

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use Selvi\Database\Builder\DML\Clauses\SelectNode;
use Selvi\Database\Builder\DML\QueryBuilder;
use Selvi\Database\DB;
use Selvi\Database\Manager;
use Selvi\Env;
use Selvi\Exception\Handler;
use Selvi\Model;
use Selvi\Model\Attributes\Aggregate;
use Selvi\Model\Attributes\BelongsTo;
use Selvi\Model\Attributes\Column;
use Selvi\Model\Attributes\Table;
use Selvi\Routing\Route;
use Selvi\Tests\Models\Departemen;
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


#[Table('departemen', 'main')]
class DepartemenEkspresi extends Model {

    #[Column('id_departemen', key: true)]
    public int $idDepartemen;

    #[Column('nm_departemen')]
    public ?string $nmDepartemen;

    #[Aggregate('summary_kontak', key: 'id_departemen', column: 'jml_kontak', expression: [
        'type' => 'function',
        'name' => 'COALESCE',
        'arguments' => [
            ['type' => 'column', 'column' => 'jml_kontak'],
            ['type' => 'literal', 'value' => 0],
        ],
    ])]
    public int $jmlKontak = 0;

    public static function summary_kontak(): QueryBuilder {
        return DB::table('kontak')
            ->select(
                'kontak.id_departemen',
                ['COUNT(kontak.id_kontak)', 'jml_kontak'])
            ->group('kontak.id_departemen');
    }

}

#[Table('unit', 'main')]
class UnitEkspresi extends Model {

    #[Column('id_unit', key: true)]
    public int $idUnit;

    #[Column('nm_unit', expression: [
        'type' => 'function',
        'name' => 'COALESCE',
        'arguments' => [
            ['type' => 'column', 'column' => 'nm_unit'],
            ['type' => 'literal', 'value' => 'Tanpa Unit'],
        ],
    ])]
    public ?string $nmUnit;

}

#[Table('kontak', 'main')]
class KontakUnitEkspresi extends Model {

    #[Column('id_kontak', key: true)]
    public int $idKontak;

    #[BelongsTo(UnitEkspresi::class, foreignKey: 'id_unit')]
    public ?UnitEkspresi $unit;

}


#[Table('departemen', 'main')]
class DepartemenResolverEkspresi extends Model {

    #[Column('id_departemen', key: true)]
    public int $idDepartemen;

    #[Column('nm_departemen')]
    public ?string $nmDepartemen;

    #[Aggregate('summary_kontak', key: 'id_departemen', column: 'jml_kontak', expression: 'formatJmlKontak')]
    public int $jmlKontak = 0;

    public static function summary_kontak(): QueryBuilder {
        return DB::table('kontak')
            ->select(
                'kontak.id_departemen',
                ['COUNT(kontak.id_kontak)', 'jml_kontak'])
            ->group('kontak.id_departemen');
    }

    public static function formatJmlKontak(array $context): array {
        return SelectNode::function('COALESCE', [
            SelectNode::column($context['column'], $context['source']),
            SelectNode::literal(0),
        ]);
    }

}

#[Table('unit', 'main')]
class UnitResolverEkspresi extends Model {

    #[Column('id_unit', key: true)]
    public int $idUnit;

    #[Column('nm_unit', expression: 'namaUnit')]
    public ?string $nmUnit;

    public static function namaUnit(array $context): array {
        return SelectNode::function('COALESCE', [
            SelectNode::column($context['column'], $context['source']),
            SelectNode::literal('Tanpa Unit'),
        ]);
    }

}

#[Table('kontak', 'main')]
class KontakUnitResolverEkspresi extends Model {

    #[Column('id_kontak', key: true)]
    public int $idKontak;

    #[BelongsTo(UnitResolverEkspresi::class, foreignKey: 'id_unit')]
    public ?UnitResolverEkspresi $unit;

}

#[Table('unit', 'main')]
class UnitNestedResolver extends Model {

    #[Column('id_unit', key: true)]
    public int $idUnit;

    #[Column('nm_unit')]
    public ?string $nmUnit;

    #[Column('id_departemen')]
    public ?int $idDepartemen = null;

    #[BelongsTo(DepartemenResolverEkspresi::class, foreignKey: 'id_departemen')]
    public ?DepartemenResolverEkspresi $departemen = null;

}

#[Table('kontak', 'main')]
class KontakNestedResolver extends Model {

    #[Column('id_kontak', key: true)]
    public int $idKontak;

    #[BelongsTo(UnitNestedResolver::class, foreignKey: 'id_unit')]
    public ?UnitNestedResolver $unit;

}

Route::get('/', function () {
    $kontak = Kontak::with('departemen')
        ->with('unit')
        ->find(5);

    return jsonResponse($kontak?->toArray());
});

Route::get('/aggregate', function () {
    $data = Departemen::with('jmlKontak')->all()->toArray();

    return jsonResponse(['data' => $data]);
});

Route::get('/nested', function () {
    $kontak = Kontak::with('unit', fn($builder) => $builder->with('departemen'))
        ->find(5, function ($query) {
            echo $query->getSql();
        });

    return jsonResponse($kontak?->toArray());
});

Route::get('/nested-aggregate', function () {
    $kontak = Kontak::with('unit', fn($builder) => $builder
        ->with('departemen', fn($inner) => $inner->with('jmlKontak')))
        ->find(5);

    return jsonResponse($kontak?->toArray());
});

Route::get('/relations', function () {
    return jsonResponse([
        'nama' => array_keys(Kontak::relations()),
        'departemen' => get_class(Kontak::relation('departemen')),
        'tidak_ada' => Kontak::relation('tidakAda'),
    ]);
});

Route::get('/tanpa-relasi', function () {
    return jsonResponse(Kontak::find(5)?->toArray());
});


Route::get('/aggregate-ekspresi', function () {
    $data = DepartemenEkspresi::with('jmlKontak')->all()->toArray();

    return jsonResponse(['data' => $data]);
});

Route::get('/nested-ekspresi', function () {
    $kontak = KontakUnitEkspresi::with('unit')->find(5);

    return jsonResponse([
        'data' => $kontak?->toArray(),
        'sql' => KontakUnitEkspresi::with('unit')->build()->getSql(),
    ]);
});

Route::get('/aggregate-resolver', function () {
    $data = DepartemenResolverEkspresi::with('jmlKontak')->all()->toArray();

    return jsonResponse([
        'data' => $data,
        'sql' => DepartemenResolverEkspresi::with('jmlKontak')->build()->getSql(),
    ]);
});

Route::get('/nested-resolver', function () {
    $kontak = KontakUnitResolverEkspresi::with('unit')->find(5);

    return jsonResponse([
        'data' => $kontak?->toArray(),
        'sql' => KontakUnitResolverEkspresi::with('unit')->build()->getSql(),
    ]);
});

Route::get('/nested-aggregate-resolver', function () {
    $query = fn() => KontakNestedResolver::with('unit', fn($builder) => $builder
        ->with('departemen', fn($inner) => $inner->with('jmlKontak')));
    $kontak = $query()->find(5);

    return jsonResponse([
        'data' => $kontak?->toArray(),
        'sql' => $query()->build()->getSql(),
    ]);
});

\Selvi\Framework::run();
