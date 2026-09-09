<?php 

namespace Selvi\Database\Drivers\MySQL;

use Override;
use Selvi\Database\Contracts\DriverInterface;

class MySQLDriver implements DriverInterface {

    #[Override]
    public function getName(): string
    {
        return 'mysql';
    }

    public function getGrammar(): MySQLGrammar
    {
        return new MySQLGrammar();
    }

    #[Override]
    public function connect(array $config): MySQLConnection
    {
        $connection = new MySQLConnection($config);
        $connection->setGrammar($this->getGrammar());
        $connection->connect();
        return $connection;
    }

}