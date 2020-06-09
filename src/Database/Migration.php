<?php 

namespace Selvi\Database;
use Selvi\Database\Manager as Database;

class Migration {

    private $db;

    public function run($args) {
        $schema = $args[0];
        $this->db = Database::get($schema);
        return response($args[0]);
    }

}