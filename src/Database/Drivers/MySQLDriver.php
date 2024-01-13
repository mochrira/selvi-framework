<?php 

namespace Selvi\Database\Drivers;

use mysqli;
use Selvi\Database\QueryResult;
use Selvi\Database\Schema;

class MySQLDriver implements Schema {

    private Array $config;
    private \mysqli $instance;

    public function __construct(Array $config)
    {
        $this->config = $config;
    }

    public function connect(): bool
    {
        if(!isset($this->instance)) {
            $this->instance = new \mysqli(
                $this->config['host'], 
                $this->config['username'], 
                $this->config['password'], 
                $this->config['database'] ?? null, 
                $this->config['port'] ?? null, 
                $this->config['socket'] ?? null
            );
        }
        return $this->instance->connect();
    }

    public function disconnect(): bool
    {
        if(isset($this->instance)) {
            return $this->instance->close();
        }
        return false;
    }

    public function query(): QueryResult
    {
        return new QueryResult();
    }

}