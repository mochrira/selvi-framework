<?php

require __DIR__.'/../../vendor/autoload.php';

use Selvi\Data\DataObject;
use Selvi\Data\DataProperty;
use Selvi\Exception\Handler;
use Selvi\Routing\Route;

Handler::setDefaultHandlers();
Handler::listen();

class KontakDTO extends DataObject {

    #[DataProperty('id_kontak')]
    public int $idKontak;

    #[DataProperty('nm_kontak')]
    public ?string $nmKontak;

    #[DataProperty('nomor', optional: true)]
    public string $nomor;

    public string $catatan = 'bukan kolom';

}

class KontakWajibDTO extends DataObject {

    #[DataProperty('id_kontak')]
    public int $idKontak;

}

Route::get('/', function () {
    $dto = KontakDTO::of((object) [
        'id_kontak' => '1',
        'nm_kontak' => 'Halo',
    ]);

    return jsonResponse($dto->toArray());
});

Route::get('/lengkap', function () {
    $dto = KontakDTO::of((object) [
        'id_kontak' => 2,
        'nm_kontak' => 'Budi',
        'nomor' => '08123',
    ]);

    return jsonResponse($dto->toArray());
});

Route::get('/wajib-hilang', function () {
    $dto = KontakWajibDTO::of((object) []);

    return jsonResponse([
        'toArray' => $dto->toArray(),
        'inisialisasi' => isset($dto->idKontak),
    ]);
});

\Selvi\Framework::run();
