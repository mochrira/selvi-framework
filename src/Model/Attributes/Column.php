<?php

namespace Selvi\Model\Attributes;

use Attribute;
use InvalidArgumentException;
use Selvi\Database\Builder\DML\Clauses\SelectNode;
use Selvi\Database\Interfaces\ICast;
use Selvi\Database\Interfaces\ISerializesCast;
use Selvi\Model\PropertyAttribute;

#[\Attribute(Attribute::TARGET_PROPERTY)]
class Column extends PropertyAttribute {

    public function __construct(
        public string $name,
        public bool $key = false,
        public ?ICast $cast = null,
        public ?ISerializesCast $serialize = null,
        public array|string|null $expression = null
    ) {
        if($this->key && $this->expression !== null) {
            throw new InvalidArgumentException('Kolom key tidak boleh memakai expression.');
        }

        if(is_string($this->expression)) {
            $this->expression = trim($this->expression);
            if($this->expression === '') {
                throw new InvalidArgumentException('Nama resolver kolom tidak boleh kosong.');
            }
            if(!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $this->expression)) {
                throw new InvalidArgumentException(
                    "Nama resolver '{$this->expression}' tidak valid."
                );
            }
        }

        if(is_array($this->expression)) {
            $type = $this->expression['type'] ?? null;
            if(!in_array($type, [
                SelectNode::TYPE_COLUMN,
                SelectNode::TYPE_FUNCTION,
                SelectNode::TYPE_LITERAL,
                SelectNode::TYPE_RAW,
            ], true)) {
                throw new InvalidArgumentException('Expression kolom harus berupa node SelectNode.');
            }
        }
    }

}
