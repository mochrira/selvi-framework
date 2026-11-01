<?php

require __DIR__.'/../../vendor/autoload.php';

use Selvi\Database\Casting\ArrayCast;
use Selvi\Database\Casting\DateTimeCast;
use Selvi\Data\DataObject;
use Selvi\Data\DataProperty;
use Selvi\Exception\Handler;
use Selvi\Routing\Route;

Handler::setDefaultHandlers();
Handler::listen();

class PengaturanDTO extends DataObject {

    #[DataProperty('id')]
    public int $id;

    #[DataProperty('opsi', cast: new ArrayCast())]
    public ?array $opsi;

    #[DataProperty('dibuat')]
    public DateTimeImmutable $dibuat;

    #[DataProperty('diperbarui', serialize: new DateTimeCast(format: DATE_ATOM))]
    public DateTimeImmutable $diperbarui;

}

Route::get('/', function () {
    $dto = PengaturanDTO::of((object) [
        'id' => 1,
        'opsi' => '{"tema":"gelap","notifikasi":true}',
        'dibuat' => '2026-11-01 09:00:00',
        'diperbarui' => '2026-11-01 10:30:00',
    ]);

    return jsonResponse($dto->toArray());
});

Route::get('/null', function () {
    $dto = PengaturanDTO::of((object) [
        'id' => 2,
        'opsi' => null,
    ]);

    return jsonResponse($dto->toArray());
});

\Selvi\Framework::run();
