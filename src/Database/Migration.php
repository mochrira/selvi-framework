<?php 

namespace Selvi\Database;
use Selvi\Database\Manager as Database;
use Selvi\Exception;

class Migration {

    private $db;

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

    public function run($args) {
        $schema = $args[0];
        $direction = $args[1] ?? 'up';
        $step = -1;
        if(in_array('--step', $args)) {
            $stepIndex = array_search('--step', $args) + 1;
            $step = $args[$stepIndex];
        }

        $this->db = Database::get($schema);
        $this->prepareTables();

        $files = $this->getFiles($direction == 'up' ? 'ASC' : 'DESC');
        foreach($files as $index => $file) {
            if($step == -1 || ($step > -1 && ($index < $step))) {

                $cek = $this->db->where([['filename', basename($file)]])->limit(1)->order(['start' => 'desc'])->get('_migration');
                if($cek->num_rows() > 0) {
                    $info = $cek->row();
                    if($info->output == "success" && $info->direction == $direction) {
                        echo 'Skipped. '.basename($file).' has been executed successfully on '.date('d F Y H:i:s', $info->finish)."\n";
                        continue;
                    }
                }

                $output = '';
                $start = time();
                try {
                    echo "Starting ".basename($file)."...\n";
                    ob_start();
                    call_user_func(include $file, $this->db, $direction);   
                    echo "success";
                    $output = ob_get_contents();
                    ob_end_clean();
                    if($output == 'success') {
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
                    echo $file." - Gagal menulis log migrasi.\n";
                };
            }
        }
        return response('Migration Done..');
    }

}