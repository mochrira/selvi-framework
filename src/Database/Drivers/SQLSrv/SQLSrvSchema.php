<?php 

namespace Selvi\Database\Drivers\SQLSrv;

use Selvi\Database\Drivers\SQLSrv\SQLSrvResult;
use Selvi\Database\Schema;

class SQLSrvSchema implements Schema {

    private Array $config;
    private $instance;

    function __construct(Array $config)
    {
        $this->config = $config;
    }

    function connect(): bool
    {
        $connString = $this->config['host'] . (isset($this->config['port']) ? ', ' . $this->config['port'] : '');
        $connInfo = [
            'UID' => $this->config['username'],
            'PWD' => $this->config['password']
        ];

        if(isset($this->config['TrustServerCertificate'])) $connInfo['TrustServerCertificate'] = $this->config['TrustServerCertificate'];
        if(isset($this->config['database'])) $connInfo['Database'] = $this->config['database'];

        $this->instance = sqlsrv_connect($connString, $connInfo);
        if(!$this->instance) {
            return false;
        }
        return true;
    }

    function disconnect(): bool
    {
        return sqlsrv_close($this->instance);
    }

    function select_db(string $db): bool
    {
        if($this->disconnect()) {
            $this->config['database'] = $db;
            return $this->connect();
        }
        return false;
    }

    function query(string $sql): SQLSrvResult
    {
        $res = sqlsrv_query($this->instance, $sql, null, ['Scrollable' => SQLSRV_CURSOR_CLIENT_BUFFERED]);
        return new SQLSrvResult($res);
    }

}