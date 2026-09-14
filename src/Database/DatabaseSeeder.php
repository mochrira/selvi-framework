<?php

declare(strict_types=1);

namespace Selvi\Database;

use Closure;
use ReflectionFunction;
use ReflectionNamedType;
use RuntimeException;
use Selvi\Exception\DatabaseException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Throwable;

/**
 * Perintah seeder di atas stack database terbaru.
 *
 * Sejajar dengan DatabaseMigration, dengan dua perbedaan mendasar: seed tidak
 * punya arah up/down (selalu satu arah), dan file seed hanya menerima Schema —
 * tanpa parameter direction.
 *
 * Kontrak file seed:
 *
 *     return function (Schema $schema) {
 *         $schema->table('pengguna')->insert([...]);
 *     };
 *
 * Jejak eksekusinya dicatat di tabel _migration yang sama dengan migrasi, memakai
 * direction 'seed' — jadi riwayatnya menyatu dengan migrasi, persis seperti
 * perilaku Seeder versi lama.
 *
 * Nama perintah default 'db:seed' (dari atribut); beri nama lain lewat konstruktor
 * bila perlu: $app->addCommand(new DatabaseSeeder('seeder')).
 *
 * @see \Selvi\Database\Schema
 * @see \Selvi\Database\MigrationLog
 */
#[AsCommand(
    name: 'db:seed',
    description: 'Menjalankan database seeder'
)]
class DatabaseSeeder extends Command {

    /**
     * Penanda arah pada tabel riwayat. Seeder selalu memakai nilai ini.
     */
    private const DIRECTION = 'seed';

    /**
     * Path seeder per nama koneksi.
     *
     * @var array<string, string[]>
     */
    private static array $paths = [];

    /**
     * Mendaftarkan beberapa path sekaligus.
     *
     * @param string[] $paths
     */
    public static function addAll(string $connection, array $paths): void {
        if(!isset(self::$paths[$connection])) self::$paths[$connection] = [];
        self::$paths[$connection] = array_merge(self::$paths[$connection], $paths);
    }

    public static function add(string $connection, string $path): void {
        self::addAll($connection, [$path]);
    }

    protected function configure(): void {
        $this->addArgument('name', InputArgument::REQUIRED, 'Nama konfigurasi database')
            ->addOption('step', 's', InputOption::VALUE_OPTIONAL, 'Jumlah file seed yang akan dijalankan', null);
    }

    /**
     * Menjalankan file seed.
     *
     * @param int $step -1 berarti tanpa batas.
     * @param ?callable(string $msg, string $status, string $type): void $logger
     */
    public function up(string $connection, int $step = -1, ?callable $logger = null): void {
        try {
            $files = $this->files($connection, $step);

            $schema = new Schema($connection);

            $log = new MigrationLog($schema);
            $log->prepare();

            foreach($files as $file) {
                $basename = basename($file);

                $last = $log->last($basename, self::DIRECTION);
                if($last !== null && $last->output === 'success') {
                    if($logger) $logger($basename . ' berhasil dijalankan pada ' . date('Y-m-d H:i:s', (int) $last->finish), 'skipped', 'warning');
                    continue;
                }

                $start = time();

                try {
                    $this->resolve($file, $schema)();
                    $log->write($basename, self::DIRECTION, $start, 'success');
                    if($logger) $logger($basename . ' berhasil dijalankan', 'success', 'success');
                } catch(DatabaseException $e) {
                    // Kegagalan SQL dicatat lengkap, lalu lanjut ke file berikutnya
                    // supaya satu file bermasalah tidak menghentikan seluruh batch.
                    $log->write($basename, self::DIRECTION, $start, 'failed', $e);
                    if($logger) $logger($basename . ' gagal dijalankan. ' . $e->getState() . ': ' . $e->getMessage(), 'failed', 'error');
                }
            }
        } catch(Throwable $e) {
            if($logger) $logger($e->getMessage(), 'failed', 'error');
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        try {
            $name = $input->getArgument('name');
            $step = $input->getOption('step');

            $step = $step === null ? -1 : (int) $step;

            if(empty($this->files($name, $step))) {
                $output->writeln('<info>Tidak ada file seed yang perlu dijalankan</info>');
            }

            if($input->isInteractive()) {
                /** @var QuestionHelper $helper */
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion(
                    '<question>Apakah Anda yakin ingin melanjutkan? (y/N)</question> ',
                    false
                );

                if(!$helper->ask($input, $output, $question)) {
                    $output->writeln('<comment>Seed dibatalkan.</comment>');
                    return Command::SUCCESS;
                }
            }

            $this->up($name, $step, $this->logger($output));

            return Command::SUCCESS;
        } catch(Throwable $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }

    /**
     * Daftar file seed untuk sebuah koneksi, terurut berdasarkan nama file.
     *
     * @return string[]
     */
    private function files(string $connection, int $step = -1): array {
        if(!isset(self::$paths[$connection])) {
            throw new RuntimeException("Belum ada path seeder yang didaftarkan untuk koneksi '{$connection}'. Panggil DatabaseSeeder::add().");
        }

        $files = [];

        foreach(self::$paths[$connection] as $path) {
            if(!is_dir($path)) continue;

            $phpFiles = glob(rtrim($path, '/') . '/*.php');
            if($phpFiles !== false) $files = array_merge($files, $phpFiles);
        }

        usort($files, fn($a, $b) => basename($a) <=> basename($b));

        if($step === -1) return $files;
        return array_slice($files, 0, $step);
    }

    /**
     * Mengubah file seed menjadi callable tanpa argumen.
     *
     * Dipakai include (bukan include_once) supaya closure selalu didapat segar,
     * dan hasilnya divalidasi agar kesalahan file terbaca jelas.
     */
    private function resolve(string $file, Schema $schema): callable {
        $basename = basename($file);

        $closure = (static function () use ($file) {
            return include $file;
        })();

        if(!$closure instanceof Closure) {
            throw new RuntimeException("File seed {$basename} harus mengembalikan closure.");
        }

        if(!$this->accepts($closure, Schema::class)) {
            throw new RuntimeException("File seed {$basename} harus menerima " . Schema::class . ' pada parameter pertamanya.');
        }

        return static fn() => $closure($schema);
    }

    /**
     * Memeriksa tipe parameter pertama sebuah closure.
     */
    private function accepts(Closure $closure, string $class): bool {
        $parameter = (new ReflectionFunction($closure))->getParameters()[0] ?? null;
        $type = $parameter?->getType();

        return $type instanceof ReflectionNamedType && is_a($type->getName(), $class, true);
    }

    /**
     * Logger bawaan dengan format [status:seed] berwarna.
     */
    private function logger(OutputInterface $output): callable {
        return function (string $msg, string $status, string $type) use ($output) {
            if($type === 'error') $status = "<error>[{$status}:" . self::DIRECTION . ']</error>';
            if($type === 'warning') $status = "<fg=yellow>[{$status}:" . self::DIRECTION . ']</>';
            if($type === 'success') $status = "<fg=green>[{$status}:" . self::DIRECTION . ']</>';
            $output->writeln("{$status} {$msg}");
        };
    }

}
