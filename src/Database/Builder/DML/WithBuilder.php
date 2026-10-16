<?php

namespace Selvi\Database\Builder\DML;

use Selvi\Model\Contracts\IRelationAttribute;

class WithBuilder {

    /** @var array<IRelationAttribute> */
    private array $relations = [];

    public function with(IRelationAttribute $relation) {
        $this->relations[] = $relation;
    }

    /**
     * @return array<IRelationAttribute>
     */
    public function relations(): array {
        return $this->relations;
    }

    public function hasRelations() : bool {
        return count($this->relations) > 0;
    }

}
