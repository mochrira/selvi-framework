<?php

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use Selvi\Database\Casting\ArrayCast;
use Selvi\Database\Casting\Converter;
use Selvi\Database\Casting\DateTimeCast;
use Selvi\Database\Interfaces\ICast;
use Selvi\Database\Interfaces\ISerializesCast;
use Selvi\Database\Manager;
use Selvi\Env;
use Selvi\Exception\Handler;
use Selvi\Model;
use Selvi\Model\Attributes\Column;
use Selvi\Model\Attributes\Table;
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

enum StatusDemo: string {

    case Aktif = 'aktif';
    case Nonaktif = 'nonaktif';

}

final class UangCast implements ICast, ISerializesCast {

    public function get(mixed $value): mixed {
        return $value === null ? null : (int) $value;
    }

    public function set(mixed $value): mixed {
        return $value === null ? null : (int) $value;
    }

    public function serialize(mixed $value): mixed {
        return $value === null ? null : 'Rp '.number_format((int) $value, 0, ',', '.');
    }

}

#[Table('kontak', 'main')]
class KontakCast extends Model {

    #[Column('id_kontak', key: true)]
    public int $idKontak;

    #[Column('nm_kontak')]
    public string $nmKontak;

    #[Column('created_at')]
    public ?DateTime $createdAt;

}

Route::get('/dasar', function () {
    $converter = Converter::instance();

    return jsonResponse([
        'int' => $converter->get('int', '12'),
        'bool' => $converter->get('bool', '0'),
        'float' => $converter->get('float', '1.5'),
        'string' => $converter->get('string', 10),
        'set' => $converter->set('int', '12'),
        'serialize' => $converter->serialize('int', 12),
    ]);
});

Route::get('/array', function () {
    $cast = new ArrayCast();

    return jsonResponse([
        'get' => $cast->get('{"tema":"gelap","notifikasi":true}'),
        'get_invalid' => $cast->get('bukan json'),
        'set' => $cast->set(['a' => 1]),
        'serialize' => $cast->serialize([['a' => 1]]),
    ]);
});

Route::get('/tanggal', function () {
    $cast = new DateTimeCast();
    $atom = new DateTimeCast(format: DATE_ATOM);

    return jsonResponse([
        'get' => $cast->get('2026-11-01 09:00:00')->format('Y-m-d H:i:s'),
        'set' => $cast->set(new DateTime('2026-11-01 09:00:00')),
        'serialize' => $cast->serialize(new DateTime('2026-11-01 09:00:00')),
        'serialize_atom' => $atom->serialize(new DateTime('2026-11-01 09:00:00')),
    ]);
});

Route::get('/enum', function () {
    $converter = Converter::instance();

    return jsonResponse([
        'get' => $converter->get(StatusDemo::class, 'aktif')->value,
        'serialize' => $converter->serialize(StatusDemo::class, StatusDemo::Nonaktif),
    ]);
});

Route::get('/custom', function () {
    Converter::instance()->register('uang', new UangCast());
    $converter = Converter::instance();

    return jsonResponse([
        'get' => $converter->get('uang', '1500'),
        'set' => $converter->set('uang', 1500),
        'serialize' => $converter->serialize('uang', 1500),
    ]);
});

Route::get('/model', function () {
    $kontak = KontakCast::find(5);

    return jsonResponse([
        'toArray' => $kontak?->toArray(),
        'toArrayDb' => $kontak?->toArrayDb(),
    ]);
});

\Selvi\Framework::run();
