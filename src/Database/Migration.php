<?php 

namespace Selvi\Database;
use Selvi\Database\Manager as Database;
use Selvi\Exception;

class Migration {

    private static $backup_path;
    public static function setBackupPath($path) {
        self::$backup_path = $path;
    }

    private $db;
    private $silent = false;

    private function getFiles($sort = 'ASC') {
        $files = [];
        $paths = $this->db->getMigrationPaths();
        foreach($paths as $path) {
            $inside = preg_grep('~.*\.php$~', scandir($path));
            foreach($inside as $i) {
                $files[] = $path.((substr($path, strlen($path) - 1, 1) !== '/') ? '/' : '').$i;
            }
        }
        if($sort == 'ASC') {
            usort($files, function ($a, $b) {
                return basename($a) > basename($b);
            });
        } else {
            usort($files, function ($a, $b) {
                return basename($a) < basename($b);
            });
        }
        return $files;
    }

    private function prepareTables() {
        $this->db->create('_migration', array(
            'id' => 'int(11) AUTO_INCREMENT PRIMARY KEY',
            'filename' => 'VARCHAR(150) NOT NULL',
            'direction' => 'VARCHAR(15) NOT NULL',
            'start' => 'INT(11) NOT NULL',
            'finish' => 'INT(11) NOT NULL',
            'output' => 'TEXT NOT NULL'
        ));
    }

    private function print($msg) {
        if(!$this->silent) {
            echo $msg;
        }
    }

    public function needUpgrade($schema) {
        $this->db = Database::get($schema);
        $this->prepareTables();

        $files = $this->getFiles('ASC');
        $filesUpgrade = [];
        foreach($files as $index => $file) {
            $cek = $this->db->where([['filename', basename($file)]])->limit(1)->order(['start' => 'desc'])->get('_migration');
            if($cek->num_rows() > 0) {
                $info = $cek->row();
                if($info->output !== "success" || $info->direction !== 'up') {
                    $filesUpgrade[] = $file;
                }
            }
        }
        return count($filesUpgrade) > 0 ? true : false;
    }

    public function run() {
        $args = func_get_args();
        $schema = $args[0];
        $this->silent = in_array('--silent', $args);
        if(in_array('--check', $args)) {
            $needUpgrade = $this->needUpgrade($schema);
            if(!$this->silent) {
                return response($needUpgrade ? 'Need Upgrade' : 'Already Updated');
            }
            return;
        }

        $direction = $args[1] ?? 'up';
        $step = -1;
        if(in_array('--step', $args)) {
            $stepIndex = array_search('--step', $args) + 1;
            $step = $args[$stepIndex];
        } else {
            if($direction == 'down') {
                $step = 1;
            }
        }

        $this->db = Database::get($schema);
        $this->prepareTables();

        $files = $this->getFiles($direction == 'up' ? 'ASC' : 'DESC');
        if(in_array('--all', $args)) {
            $step = count($files);
        }

        $config = $this->db->getConfig();
        $path = self::$backup_path.'/'.$config['database'];
        if(!is_dir($path)) {
            mkdir($path, 0775, true);
        }
        $backup_file = $path.'/'.$config['database'].'_'.time().'.sql';
        $this->print("Backing up database...\n");

        if(!in_array('--no-backup', $args)) {
            exec('mysqldump --host='.$config['host'].' --user='.$config['username'].' --password='.$config['password'].' '.$config['database'].' --result-file='.$backup_file.' 2>&1', $output);
            if(!$this->silent) { 
                foreach($output as $o) {
                    echo $o."\n";
                }
            }
        }

        foreach($files as $index => $file) {
            if($step == -1 || ($step > -1 && ($index < $step))) {

                $cek = $this->db->where([['filename', basename($file)]])->limit(1)->order(['start' => 'desc'])->get('_migration');
                if($cek->num_rows() > 0) {
                    $info = $cek->row();
                    if($info->output == "success" && $info->direction == $direction) {
                        $this->print('Skipped. '.basename($file).' has been executed successfully on '.date('d F Y H:i:s', $info->finish)."\n");
                        continue;
                    }
                }

                $output = '';
                $start = time();
                try {
                    if($this->silent == false) {
                        echo "Starting ".basename($file)."...\n";
                    }
                    ob_start();
                    call_user_func(include $file, $this->db, $direction);   
                    echo "success";
                    $output = ob_get_contents();
                    ob_end_clean();
                    if($output == 'success' && $this->silent == false) {
                        echo basename($file)." successfully executed\n";
                    }
                } catch(Exception $e) {
                    $output = basename($file)." failed\n";
                }
                $finish = time();

                if(!$this->db->insert('_migration', array(
                    'direction' => $direction,
                    'start' => $start,
                    'finish' => $finish,
                    'filename' => basename($file),
                    'output' => $output
                ))) {
                    if($silent == false) {
                        $this->print($file." - Gagal menulis log migrasi.\n");
                    }
                };
            }
        }
        if(!$this->silent) {
            return response('Migration Done..');
        }
        return;
    }

}