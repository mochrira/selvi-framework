<?php 

namespace Selvi\Model\Attributes;

use Attribute;
use Override;
use InvalidArgumentException;
use Selvi\Data\DataObject;
use Selvi\Data\DataRow;
use Selvi\Database\Builder\DML\Clauses\SelectNode;
use Selvi\Database\Interfaces\ICast;
use Selvi\Database\Interfaces\ISerializesCast;
use Selvi\Model\PropertyAttribute;

#[\Attribute(Attribute::TARGET_PROPERTY)]
class Aggregate extends PropertyAttribute {

    public function __construct(
        public string $query,
        public string $key,
        public string $column,
        public ?ICast $cast = null,
        public ?ISerializesCast $serialize = null,
        public array|string|null $expression = null
    ) { 
        $this->name = $column;

        if(is_string($this->expression)) {
            $this->expression = trim($this->expression);
            if($this->expression === '') {
                throw new InvalidArgumentException('Nama resolver agregat tidak boleh kosong.');
            }
            if(!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $this->expression)) {
                throw new InvalidArgumentException(
                    "Nama resolver agregat '{$this->expression}' tidak valid."
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
                throw new InvalidArgumentException('Expression agregat harus berupa node SelectNode.');
            }
        }
    }

    #[Override]
    public function assign(object $target, DataRow $row): void {
        if(!$row->hasAny($row->append("{$this->query}_"))) return;

        DataObject::assignValue(
            $target,
            $this->propertyName(),
            $row->getAppended("{$this->query}__{$this->column}")
        );
    }

}
