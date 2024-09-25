<?php 

namespace Selvi\Database;

use Selvi\Exception;
use Selvi\Database\Manager;
use Selvi\Exception\DatabaseException;

class Migration {

    private static $paths = [];

    static function addMigrations($schema, $paths) {
        if(!isset(self::$paths[$schema])) self::$paths[$schema] = [];
        if(is_array($paths)) self::$paths[$schema] = array_merge(self::$paths[$schema], $paths);
    }

    static function addMigration($schema, $path) {
        self::addMigrations($schema, [$path]);
    }

    private function getFiles($paths, $sort = 'ASC', $step = -1) {
        foreach($paths as $path) {
            $inside = preg_grep('~.*\.php$~', scandir($path));
            foreach($inside as $i) {
                $files[] = $path.((substr($path, strlen($path) - 1, 1) !== '/') ? '/' : '').$i;
            }
        }
        usort($files, function ($a, $b) use ($sort) {
            return $sort == 'ASC' ? basename($a) > basename($b) : basename($a) < basename($b);
        });
        if($step == -1) return $files;
        return array_splice($files, 0, $step);
    }

    private function prepareTables($db) {
        return $db->prepareMigrationTables();
    }

    private function report($db, $filename, $direction, $start, $output , array $log = null) {

        $error_msg = $log['msg'] ?? null;
        $error_state = $log['state'] ?? null;
        $error_query = $log['query'] ?? null;

        $config = $db->getConfig();
        return $db->insert('_migration', [
            'filename' => $filename, 
            'direction' => $direction,
            'start' => $start,
            'finish' => time(),
            'output' => $output,
            'dbuser' => $config['username'],
            'error_msg' => $error_msg,
            'error_state' => $error_state,
            'error_query' => $error_query
        ]);
    }

    private function lastrecord($db, $filename, string $direction = null) {
        if ($direction != null) $db->where([['direction', $direction]]);
        return $db->where([['filename', $filename]])->offset(0)->limit(1)
            ->order(['start' => 'DESC'])->get('_migration')->row();
    }

    function run(string $schema = null, string $direction = null, string $stepArgs = null, string $stepAll = null) {
        $isCli = php_sapi_name() == 'cli';

        $args = func_get_args();
        
        if ($schema == null) $schema = isset($args[0]) ? $args[0] : null;
        if(!isset($schema)) throw new Exception("First arguments must be a valid database name", "migration/run/invalid-arguments");

        if ($direction == null) $direction = isset($args[1]) ? $args[1] : null;
        if(!isset($direction)) throw new Exception("Second arguments must be direction of migration", "migration/run/invalid-arguments");

        if ($isCli) {
          echo "Anda yakin akan menjalankan migrasi pada database '{$schema}' dengan direction '{$direction}' ? (Y/n) ";
          $handle = fopen ("php://stdin","r");
          $line = fgets($handle);
          if(trim($line) != 'Y'){
              fclose($handle);
              return response('Proses digagalkan.');
          }
          fclose($handle);
        }

        $step = -1;
        if($direction == 'down') $step = 1;
        $stepIndex = ($isCli) ? \array_search('--step', $args) : ($stepArgs !== null ? (int)$stepArgs : false);
        if($stepIndex !== false) {
          if ($isCli) {
            if(!\is_numeric($args[$stepIndex + 1])) throw new Exception("Step arguments must be followed by number", "migration/run/invalid-step");
            $step = (int)$args[$stepIndex + 1];
          } else {
            $step = (int)$stepIndex;
          };
        }

        $allIndex = ($isCli) ? \array_search('--all', $args) : ($stepAll !== null ? (int)$stepAll : false);
        if($allIndex !== false) {
          if($stepIndex !== false) throw new Exception(($isCli) ? "--step argument not allowed to be used along side --all argument" : "step argument not allowed to be used along side all argument", "migration/run/invalid-useage");
          $step = -1;
        }

        $db = Manager::get($schema);
        if(!$db) throw new Exception("Invalid database \"".$schema."\"", "migration/run/invalid-db");

        $paths = isset(self::$paths[$schema]) ? self::$paths[$schema] : [];
        $files = $this->getFiles($paths, $direction == 'down' ? 'DESC' : 'ASC', $step);

        $this->prepareTables($db);
        $resultMigration = [];
        foreach($files as $index => $file) {
            $label = ($isCli) ? "[".($index + 1)."] ".$file : $file;
            $start = time();
            try {
                if(!is_file($file)) throw new Exception("File not found : ".$file, "migration/run/invalid-file");
                if($direction == 'down') {
                    $cekUp = $this->lastrecord($db, basename($file), direction:'up');
                    
                    if($cekUp == null) {
                        if($isCli){
                          echo "[skipped] [direction=".$direction."] ".$label." up direction never executed before\n";
                        } else {
                          $resultMigration[] = [
                            "tanggal" => date('d F Y H:i:s', $cekUp->finish),
                            "direction" => $direction,
                            "fileName" => $label,
                            "result" => "skipped",
                            "message" => "up direction never executed before"
                          ];
                        }
                        continue;
                    }

                    if ($cekUp != null && $cekUp->output == 'failed') {
                        if($isCli) {
                          echo "[skipped] [direction=".$direction."] ".$label." up direction failed\n";
                        } else {
                          $resultMigration[] = [
                            "tanggal" => date('d F Y H:i:s', $cekUp->finish),
                            "direction" => $direction,
                            "fileName" => $label,
                            "result" => "skipped",
                            "message" => "up direction failed"
                          ];
                        }
                        continue;
                    }
                }

                $cek = $this->lastrecord($db, basename($file));
                if($cek != null && ($cek->output == "success" && $cek->direction == $direction)) {
                  if($isCli){
                    echo "[skipped] [direction=".$direction."] ".$label.' success at '.date('d F Y H:i:s', $cek->finish)."\n";
                  } else {
                    $resultMigration[] = [
                      "tanggal" => date('d F Y H:i:s', $cek->finish),
                      "direction" => $direction,
                      "fileName" => $label,
                      "result" => "skipped",
                    ];
                  }
                  continue;
                }

                \call_user_func(include_once $file, $db, $direction);
                $this->report($db, basename($file), $direction, $start, 'success');
                if($isCli) {
                  echo "[success] ".$label."\n";
                } else {
                  $resultMigration[] = [
                    "tanggal" => date('d F Y H:i:s'),
                    "direction" => $direction,
                    "fileName" => $label,
                    "result" => "success",
                  ];
                }
            } catch(DatabaseException $e) {
                try {
                    $log = [
                        'msg' => $e->getMessage(),
                        'state' => $e->getState(),
                        'query' => $e->getSql()
                    ]; 
                    $this->report($db, basename($file), $direction, $start, 'failed', log: $log);
                    if($isCli) echo "[failed] ". $label."\n";
                    throw $e;
                } catch(Exception $e) {
                    throw $e;
                }
            }
        }
        if ($isCli) {
          return response('Migration Done. Files Count : '.count($files));
        } else {
          return $resultMigration;
        }
    }

}