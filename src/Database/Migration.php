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
 * Perintah migrasi database.
 *
 * Alur dan perilakunya: argumen name+direction, opsi --step/--all, konfirmasi
 * interaktif, format logger [status:direction], aturan skip berdasarkan record
 * terakhir, dan tabel riwayat _migration. Seluruh akses database memakai handle
 * Schema (yang membungkus Manager + ConnectionInterface + QueryBuilder +
 * SchemaBuilder), dan penamaan perintah memakai atribut #[AsCommand].
 *
 * Nama perintah default 'db:migrate' (dari atribut). Beri nama lain lewat
 * konstruktor bila perlu:
 *
 *     $app->addCommand(new Migration());          // db:migrate
 *     $app->addCommand(new Migration('migrate')); // migrate
 *
 * Path migrasi didaftarkan lewat registry statis:
 *
 *     Migration::add('main', BASEPATH.'/app/Migrations');
 *
 * Kontrak file migrasi — Schema sudah terikat ke koneksi yang diminta, mis. 'main'
 * pada perintah `db:migrate main up`:
 *
 *     return function (Schema $schema, string $direction) { ... };
 *
 * @see \Selvi\Database\Schema
 * @see \Selvi\Database\MigrationLog
 */
#[AsCommand(
    name: 'db:migrate',
    description: 'Menjalankan migrasi database'
)]
class Migration extends Command {

    /**
     * Path migrasi per nama koneksi.
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
            ->addArgument('direction', InputArgument::REQUIRED, 'Arah migrasi (up/down)')
            ->addOption('step', 's', InputOption::VALUE_OPTIONAL, 'Jumlah file migrasi yang akan dijalankan', null)
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Menjalankan semua migrasi');
    }

    /**
     * Menjalankan seluruh migrasi ke depan.
     *
     * @param int $step -1 berarti tanpa batas.
     * @param ?callable(string $msg, string $status, string $type): void $logger
     */
    public function up(string $connection, int $step = -1, ?callable $logger = null): void {
        $this->migrate($connection, 'up', $step, $logger);
    }

    /**
     * Mengembalikan migrasi ke belakang.
     *
     * @param int $step -1 berarti tanpa batas.
     * @param ?callable(string $msg, string $status, string $type): void $logger
     */
    public function down(string $connection, int $step = 1, ?callable $logger = null): void {
        $this->migrate($connection, 'down', $step, $logger);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        try {
            $name = $input->getArgument('name');
            $direction = $input->getArgument('direction');

            if(!in_array($direction, ['up', 'down'], true)) {
                $output->writeln("<error>Arah migrasi '{$direction}' tidak dikenali. Gunakan 'up' atau 'down'.</error>");
                return Command::FAILURE;
            }

            $all = $input->getOption('all');
            $step = $input->getOption('step');

            if($all) {
                $step = -1;
            } else if($step === null) {
                $step = ($direction === 'up' ? -1 : 1);
            } else {
                $step = (int) $step;
            }

            $files = $this->files($name, $direction === 'down' ? 'DESC' : 'ASC', $step);
            if(empty($files)) {
                $output->writeln('<info>Tidak ada file migrasi yang perlu dijalankan</info>');
            }

            if($input->isInteractive()) {
                /** @var QuestionHelper $helper */
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion(
                    '<question>Apakah Anda yakin ingin melanjutkan? (y/N)</question> ',
                    false
                );

                if(!$helper->ask($input, $output, $question)) {
                    $output->writeln('<comment>Migrasi dibatalkan.</comment>');
                    return Command::SUCCESS;
                }
            }

            $logger = $this->logger($output, $direction);

            if($direction === 'up') $this->up($name, $step, $logger);
            else $this->down($name, $step, $logger);

            return Command::SUCCESS;
        } catch(Throwable $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }

    /**
     * Inti eksekusi, dipakai bersama oleh up() dan down().
     *
     * Satu-satunya perbedaan antar arah adalah urutan file, default step, dan
     * label yang muncul di logger; sisanya identik.
     *
     * @param ?callable(string $msg, string $status, string $type): void $logger
     */
    private function migrate(string $connection, string $direction, int $step, ?callable $logger): void {
        try {
            $files = $this->files($connection, $direction === 'down' ? 'DESC' : 'ASC', $step);

            // Handle yang sudah terikat koneksi — inilah yang diterima file migrasi.
            $schema = new Schema($connection);

            $log = new MigrationLog($schema);
            $log->prepare();

            foreach($files as $file) {
                $basename = basename($file);

                $last = $log->last($basename);
                if($last !== null && $last->direction === $direction && $last->output === 'success') {
                    if($logger) $logger($basename . ' berhasil dijalankan pada ' . date('Y-m-d H:i:s', (int) $last->finish), 'skipped', 'warning');
                    continue;
                }

                $start = time();

                try {
                    $this->resolve($file, $schema)($direction);
                    $log->write($basename, $direction, $start, 'success');
                    if($logger) $logger($basename . ' berhasil dijalankan', 'success', 'success');
                } catch(DatabaseException $e) {
                    // Kegagalan SQL dicatat lengkap, lalu lanjut ke file berikutnya
                    // supaya satu file bermasalah tidak menghentikan seluruh batch.
                    $log->write($basename, $direction, $start, 'failed', $e);
                    if($logger) $logger($basename . ' gagal dijalankan. ' . $e->getState() . ': ' . $e->getMessage(), 'failed', 'error');
                }
            }
        } catch(Throwable $e) {
            if($logger) $logger($e->getMessage(), 'failed', 'error');
        }
    }

    /**
     * Daftar file migrasi untuk sebuah koneksi, terurut berdasarkan nama file.
     *
     * @return string[]
     */
    private function files(string $connection, string $sort = 'ASC', int $step = -1): array {
        if(!isset(self::$paths[$connection])) {
            throw new RuntimeException("Belum ada path migrasi yang didaftarkan untuk koneksi '{$connection}'. Panggil Migration::add().");
        }

        $files = [];

        foreach(self::$paths[$connection] as $path) {
            if(!is_dir($path)) continue;

            $phpFiles = glob(rtrim($path, '/') . '/*.php');
            if($phpFiles !== false) $files = array_merge($files, $phpFiles);
        }

        usort($files, function ($a, $b) use ($sort) {
            $comparison = basename($a) <=> basename($b);
            return $sort === 'ASC' ? $comparison : -$comparison;
        });

        if($step === -1) return $files;
        return array_slice($files, 0, $step);
    }

    /**
     * Mengubah file migrasi menjadi callable satu argumen ($direction).
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
            throw new RuntimeException("File migrasi {$basename} harus mengembalikan closure.");
        }

        if(!$this->accepts($closure, Schema::class)) {
            throw new RuntimeException("File migrasi {$basename} harus menerima " . Schema::class . " pada parameter pertamanya.");
        }

        return static fn(string $direction) => $closure($schema, $direction);
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
     * Logger bawaan dengan format [status:direction] berwarna.
     */
    private function logger(OutputInterface $output, string $direction): callable {
        return function (string $msg, string $status, string $type) use ($output, $direction) {
            if($type === 'error') $status = "<error>[{$status}:{$direction}]</error>";
            if($type === 'warning') $status = "<fg=yellow>[{$status}:{$direction}]</>";
            if($type === 'success') $status = "<fg=green>[{$status}:{$direction}]</>";
            $output->writeln("{$status} {$msg}");
        };
    }

}
