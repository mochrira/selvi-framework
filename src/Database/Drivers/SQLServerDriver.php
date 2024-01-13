<?php 

namespace Selvi\Database\Drivers;

use Selvi\Database\QueryResult;
use Selvi\Database\Schema;

class SQLServerDriver implements Schema {

    private Array $config;

    public function __construct(Array $config)
    {
        $this->config = $config;
    }

    public function connect(): bool
    {
        
    }

    public function disconnect(): bool
    {
        
    }

    public function query(): QueryResult
    {
        return new QueryResult();
    }

}