<?php 

namespace Selvi\Database;

use Selvi\Database\QueryResult;

public class interface Schema {

    public function connect(): void {

    }

    public function disconnect(): void {

    }

    public function query(): QueryResult;
    public function get() {

    }

    public function insert() {

    }

    public function update() {

    }

    public function delete() {

    }

}