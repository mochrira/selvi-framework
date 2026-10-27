<?php 

namespace Selvi\Database\Builder\DML;

use Closure;

class WithBuilder {

    /** @var array<string, Closure> */
    public array $clauses = [];

    public function with(string $name, ?Closure $closure = null) : static {
        $this->clauses[$name] = $closure;
        return $this;
    }

    public function hasClauses() {
        return count($this->clauses) > 0;
    }

}