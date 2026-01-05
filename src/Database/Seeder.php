<?php

namespace Selvi\Database;

use Selvi\Exception;
use Selvi\Exception\DatabaseException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Helper\QuestionHelper;

class Seeder extends Command {

    private static $paths = [];

    static function addAll($schema, $paths) {
        if(!isset(self::$paths[$schema])) self::$paths[$schema] = [];
        if(is_array($paths)) self::$paths[$schema] = array_merge(self::$paths[$schema], $paths);
    }

    static function add($schema, $path) {
        self::addAll($schema, [$path]);
    }
    
    protected static $defaultName = 'seeder';

    protected function configure(): void {
        $this->setName('seeder')
            ->setDescription('Menjalankan database seeder')
            ->addArgument('name', InputArgument::REQUIRED, 'Nama konfigurasi database')
            ->addOption('step', 's', InputOption::VALUE_OPTIONAL, 'Jumlah file seed yang akan dijalankan', null);
    }

    private function getFiles($paths, $sort = 'ASC', $step = -1) {
        $files = [];
        
        foreach($paths as $path) {
            if (!is_dir($path)) {
                continue;
            }
            
            $phpFiles = glob(rtrim($path, '/') . '/*.php');
            if ($phpFiles !== false) {
                $files = array_merge($files, $phpFiles);
            }
        }
        
        usort($files, function ($a, $b) use ($sort) {
            $comparison = basename($a) <=> basename($b);
            return $sort === 'ASC' ? $comparison : -$comparison;
        });
        
        if($step == -1) return $files;
        return array_slice($files, 0, $step);
    }
    
    private function report($db, $filename, $start, $output, ?array $log = null) {

        $error_msg = $log['msg'] ?? null;
        $error_state = $log['state'] ?? null;
        $error_query = $log['query'] ?? null;

        $config = $db->getConfig();
        return $db->insert('_migration', [
            'filename' => $filename, 
            'direction' => 'seed',
            'start' => $start,
            'finish' => time(),
            'output' => $output,
            'dbuser' => $config['username'],
            'error_msg' => $error_msg,
            'error_state' => $error_state,
            'error_query' => $error_query
        ]);
    }

    private function getlastrecord(Schema $db, string $file) {
        return $db->where([['filename', $file], ['direction', 'seed']])
            ->offset(0)->limit(1)->order(['start' => 'DESC'])
            ->get('_migration')->row();
    }

    function up(string $dbName, int $step = -1, ?callable $logger = null) {
        try {
            $files = $this->getFiles(
                paths: self::$paths[$dbName], 
                sort: 'ASC',
                step: $step
            );

            $db = Manager::get($dbName);
            $db->prepareMigrationTables();

            foreach($files as $file) {
                $basename = basename($file);
                $lastRecord = $this->getlastrecord($db, $basename);
                if($lastRecord != null) {
                    if($lastRecord->output == 'success') {
                        if($logger) $logger($basename." berhasil dijalankan pada " . date('Y-m-d H:i:s', $lastRecord->finish), 'skipped', 'warning');
                        continue;
                    }
                }

                try {
                    $start = time();
                    \call_user_func(include_once $file, $db);
                    $this->report($db, $basename, $start, 'success');
                    if($logger) $logger($basename . " berhasil dijalankan", 'success', 'success');
                } catch(DatabaseException $e) {
                    $log = [
                        'msg' => $e->getMessage(),
                        'state' => $e->getState(),
                        'query' => $e->getSql()
                    ];
                    $this->report($db, $basename, $start, 'failed', log: $log);
                    if($logger) $logger($basename . " gagal dijalankan. {$log['state']}: {$log['msg']}", 'failed', 'error');
                }
            }
        } catch(Exception $e) {
            if($logger) $logger($e->getMessage());
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int {
        try {
            $name = $input->getArgument('name');
            $step = $input->getOption('step');

            if($step == null) {
                $step = -1;
            } else {
                $step = (int) $step;
            }

            $files = $this->getFiles(
                paths: self::$paths[$name], 
                sort: 'ASC',
                step: $step
            );
            if(empty($files)) $output('<info>Tidak ada file seed yang perlu dijalankan</info>');
            
            if($input->isInteractive()) {
                /** @var QuestionHelper $helper */
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion(
                    question: '<question>Apakah Anda yakin ingin melanjutkan? (y/N)</question> ',
                    default: false
                );
                
                if (!$helper->ask($input, $output, $question)) {
                    $output->writeln('<comment>Seed dibatalkan.</comment>');
                    return Command::SUCCESS;
                }
            }
            
            $logger = function (string $msg, string $status, string $type) use ($output) {
                if($type == 'error') $status = "<error>[{$status}:seed]</error>";
                if($type == 'warning') $status = "<fg=yellow>[{$status}:seed]</>";
                if($type == 'success') $status = "<fg=green>[{$status}:seed]</>";
                $output->writeln("{$status} {$msg}");
            };

            $this->up($name, $step, $logger);

            return Command::SUCCESS;
        } catch(Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }

}