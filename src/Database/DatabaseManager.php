<?php 

declare(strict_types=1);

namespace Selvi\Database;

use InvalidArgumentException;
use Selvi\Database\Contracts\ConnectionInterface;
use Selvi\Database\Drivers\MySQL\MySQLConnection;

/**
 * Registry koneksi database.
 *
 * API-nya: add / get / default / has. Yang disimpan dan dikembalikan adalah
 * ConnectionInterface.
 */
class DatabaseManager {

    /**
     * Driver yang sudah punya implementasi ConnectionInterface.
     *
     * sqlsrv belum terdaftar karena belum ada SQLSrvConnection.
     *
     * @var array<string, class-string<ConnectionInterface>>
     */
    private static array $drivers = [
        'mysql' => MySQLConnection::class
    ];

    /**
     * @var array<string, ConnectionInterface>
     */
    private static array $connections = [];

    /**
     * Mendaftarkan koneksi. Nama yang sudah terdaftar tidak ditimpa.
     */
    public static function add(string $name, array $config): void {
        if(isset(self::$connections[$name])) return;

        $driver = $config['driver'] ?? 'mysql';

        if(!isset(self::$drivers[$driver])) {
            throw new InvalidArgumentException("Driver '{$driver}' belum punya implementasi ConnectionInterface.");
        }

        $class = self::$drivers[$driver];

        self::$connections[$name] = new $class($config);
    }

    /**
     * Mengambil koneksi yang sudah terdaftar.
     *
     * Nama yang tidak terdaftar langsung dilempar exception dengan pesan yang
     * jelas. Gunakan has() bila ingin mengecek lebih dulu.
     */
    public static function get(string $name): ConnectionInterface {
        if(!self::has($name)) {
            throw new InvalidArgumentException("Koneksi '{$name}' tidak terdaftar.");
        }

        return self::$connections[$name];
    }

    /**
     * Koneksi pertama yang terdaftar, atau null bila belum ada.
     */
    public static function default(): ConnectionInterface | null {
        $keys = array_keys(self::$connections);

        if(empty($keys)) return null;

        return self::get($keys[0]);
    }

    public static function has(string $name): bool {
        return isset(self::$connections[$name]);
    }

}
