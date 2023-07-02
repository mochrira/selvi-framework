<?php 

namespace Selvi\Database;

use Selvi\Exception;
use Selvi\Database\Manager;

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
        return $db->create('_migration', array(
            'id' => 'int(11) AUTO_INCREMENT PRIMARY KEY',
            'filename' => 'VARCHAR(150) NOT NULL',
            'direction' => 'VARCHAR(15) NOT NULL',
            'start' => 'INT(11) NOT NULL',
            'finish' => 'INT(11) NOT NULL',
            'output' => 'TEXT NOT NULL'
        ));
    }

    private function report($db, $filename, $direction, $start, $output) {
        return $db->insert('_migration', [
            'filename' => $filename, 
            'direction' => $direction,
            'start' => $start,
            'finish' => mktime(),
            'output' => $output
        ]);
    }

    private function lastrecord($db, $filename) {
        return $db->where([['filename', $filename]])->limit(1)
            ->order(['start' => 'desc'])->get('_migration')->row();
    }

    function run() {
        $args = func_get_args();
        
        $schema = isset($args[0]) ? $args[0] : null;
        if(!isset($schema)) throw new Exception("First arguments must be a valid database name", "migration/run/invalid-arguments");

        $direction = isset($args[1]) ? $args[1] : null;
        if(!isset($direction)) throw new Exception("Second arguments must be direction of migration", "migration/run/invalid-arguments");

        $step = -1;
        if($direction == 'down') $step = 1;

        $stepIndex = \array_search('--step', $args);
        if($stepIndex !== false) {
            if(!\is_numeric($args[$stepIndex + 1])) throw new Exception("Step arguments must be followed by number", "migration/run/invalid-step");
            $step = (int)$args[$stepIndex + 1];
        }

        $allIndex = \array_search('--all', $args);
        if($allIndex !== false) {
            if($stepIndex !== false) throw new Exception("--step argument not allowed to be used along side --all argument", "migration/run/invalid-useage");
            $step = -1;
        }

        $db = Manager::get($schema);
        if(!$db) throw new Exception("Invalid database \"".$schema."\"", "migration/run/invalid-db");

        $paths = isset(self::$paths[$schema]) ? self::$paths[$schema] : [];
        $files = $this->getFiles($paths, $direction == 'down' ? 'DESC' : 'ASC', $step);

        $prepareResult = $this->prepareTables($db);
        if($prepareResult === false) throw new Exception("Failed to prepare _migration table");

        foreach($files as $index => $file) {
            $label = "[".($index + 1)."] ".$file;
            $start = mktime();
            try {
                if(!is_file($file)) throw new Exception("File not found : ".$file, "migration/run/invalid-file");

                $cek = $this->lastrecord($db, basename($file));
                if($cek == null && $direction == 'down') {
                    echo "[skipped] [direction=".$direction."] ".$label." up direction never executed before\n";
                    continue;
                }

                if($cek != null && ($cek->output == "success" && $cek->direction == $direction)) {
                    echo "[skipped] [direction=".$direction."] ".$label.' success at '.date('d F Y H:i:s', $cek->finish)."\n";
                    continue;
                }

                \call_user_func(include_once $file, $db, $direction);
                $this->report($db, basename($file), $direction, $start, 'success');
                echo "[success] ".$label."\n";
            } catch(Exception $e) {
                try {
                    $this->report($db, basename($file), $direction, $start, 'failed');
                    echo "[failed] ". $label."\n";
                    throw $e;
                } catch(Exception $e) {
                    throw $e;
                }
            }
        }

        return response('Migration Done. Files Count : '.count($files));
    }

}