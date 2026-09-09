<?php 

namespace Selvi\Database;

use RuntimeException;
use Selvi\Database\Contracts\ConnectionInterface;
use Selvi\Database\Contracts\DriverInterface;
use Selvi\Database\Drivers\MySQL\MySQLDriver;

class DatabaseManager {

    /** @var array<string, string> */
    private static array $drivers = [
        'mysql' => MySQLDriver::class
    ];

    /** @var array<string, DriverInterface> */
    private static array $driverInstances = [];

    /** @var array<string, ConnectionInterface> */
    private static array $connections = [];

    private static ?string $default = null;

    /**
     * Daftarkan driver agar bisa dipakai lewat config['driver']
     */
    public static function registerDriver(string $name, string $driver): void
    {
        self::$drivers[$name] = $driver;
    }

    /**
     * Tambah koneksi baru. Koneksi pertama otomatis jadi default.
     */
    public static function add(string $name, array $config): ConnectionInterface
    {
        $connection = self::createConnection($config);
        self::$connections[$name] = $connection;
        self::$default ??= $name;
        return $connection;
    }

    /**
     * Ambil koneksi. Tanpa nama -> koneksi default.
     */
    public static function get(?string $name = null): ?ConnectionInterface
    {
        $name ??= self::$default;
        return self::$connections[$name] ?? null;
    }

    public static function has(string $name): bool
    {
        return isset(self::$connections[$name]);
    }

    /**
     * Hapus koneksi sekaligus menutupnya.
     */
    public static function remove(string $name): bool
    {
        if (!isset(self::$connections[$name])) return false;

        self::$connections[$name]->disconnect();
        unset(self::$connections[$name]);

        // bila default dihapus, pindah ke koneksi pertama yang tersisa
        if (self::$default === $name) {
            self::$default = array_key_first(self::$connections);
        }
        return true;
    }

    public static function setDefault(string $name): bool
    {
        if (!isset(self::$connections[$name])) return false;
        self::$default = $name;
        return true;
    }

    public static function getDefault(): ?ConnectionInterface
    {
        return self::$default !== null ? self::get(self::$default) : null;
    }

    /**
     * Nama-nama koneksi yang terdaftar.
     */
    public static function names(): array
    {
        return array_keys(self::$connections);
    }

    public static function disconnectAll(): void
    {
        foreach (self::$connections as $connection) {
            $connection->disconnect();
        }
    }

    private static function createConnection(array $config): ConnectionInterface
    {
        $driverKey = $config['driver'] ?? 'mysql';
        if (is_string($driverKey) && isset(self::$drivers[$driverKey])) {
            self::$driverInstances[$driverKey] = new self::$drivers[$driverKey]();
        }

        if (!isset(self::$driverInstances[$driverKey]) || !self::$driverInstances[$driverKey] instanceof DriverInterface) {
            throw new RuntimeException("Driver database '{$driverKey}' tidak terdaftar. Daftarkan lewat DatabaseManager::registerDriver().");
        }

        return self::$driverInstances[$driverKey]->connect($config);
    }

}