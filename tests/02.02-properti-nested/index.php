<?php

require __DIR__.'/../../vendor/autoload.php';

use Selvi\Data\DataObject;
use Selvi\Data\DataProperty;
use Selvi\Exception\Handler;
use Selvi\Routing\Route;

Handler::setDefaultHandlers();
Handler::listen();

class DepartemenDTO extends DataObject {

    #[DataProperty('id_departemen')]
    public int $idDepartemen;

    #[DataProperty('nm_departemen')]
    public ?string $nmDepartemen;

}

class UnitDTO extends DataObject {

    #[DataProperty('id_unit')]
    public int $idUnit;

    #[DataProperty('nm_unit')]
    public ?string $nmUnit;

    #[DataProperty(prefix: 'departemen')]
    public ?DepartemenDTO $departemen;

}

class KontakDTO extends DataObject {

    #[DataProperty('id_kontak')]
    public int $idKontak;

    #[DataProperty('nm_kontak')]
    public ?string $nmKontak;

    #[DataProperty(prefix: 'unit', optional: true)]
    public UnitDTO $unit;

    #[DataProperty(prefix: 'departemen', optional: true)]
    public DepartemenDTO $departemen;

}

class GrupDTO extends DataObject {

    #[DataProperty('id_grup')]
    public int $idGrup;

    #[DataProperty('nm_grup')]
    public ?string $nmGrup;

}

class KontakGrupDTO extends DataObject {

    #[DataProperty('id_kontak')]
    public int $idKontak;

    #[DataProperty('grup')]
    public ?GrupDTO $grup;

}

class KontakGrupWajibDTO extends DataObject {

    #[DataProperty('id_kontak')]
    public int $idKontak;

    #[DataProperty('grup')]
    public GrupDTO $grup;

}

Route::get('/', function () {
    $dto = KontakDTO::of((object) [
        'id_kontak' => 1,
        'nm_kontak' => 'Halo',
        'departemen__id_departemen' => 2,
        'departemen__nm_departemen' => 'Sales',
    ]);

    return jsonResponse($dto->toArray());
});

Route::get('/dua-tingkat', function () {
    $dto = KontakDTO::of((object) [
        'id_kontak' => 3,
        'nm_kontak' => 'Budi',
        'unit__id_unit' => 4,
        'unit__nm_unit' => 'Marketing',
        'unit_departemen__id_departemen' => 2,
        'unit_departemen__nm_departemen' => 'Sales',
    ]);

    return jsonResponse($dto->toArray());
});

Route::get('/objek-bersarang', function () {
    $grup = new stdClass();
    $grup->id_grup = 5;
    $grup->nm_grup = 'Mitra';

    $dto = KontakGrupDTO::of((object) [
        'id_kontak' => 6,
        'grup' => $grup,
    ]);

    return jsonResponse($dto->toArray());
});

Route::get('/tanpa-anak', function () {
    $dto = KontakDTO::of((object) [
        'id_kontak' => 7,
        'nm_kontak' => 'Tanpa Relasi',
    ]);

    return jsonResponse($dto->toArray());
});

Route::get('/objek-null', function () {
    $dto = KontakGrupDTO::of((object) [
        'id_kontak' => 8,
        'grup' => null,
    ]);

    return jsonResponse($dto->toArray());
});

Route::get('/objek-null-wajib', function () {
    $dto = KontakGrupWajibDTO::of((object) [
        'id_kontak' => 9,
        'grup' => null,
    ]);

    return jsonResponse($dto->toArray());
});

\Selvi\Framework::run();
