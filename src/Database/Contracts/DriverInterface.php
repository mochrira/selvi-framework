<?php 

namespace Selvi\Database\Contracts;

interface DriverInterface {

    public function getName(): string;
    public function connect(Array $config): ConnectionInterface;

}