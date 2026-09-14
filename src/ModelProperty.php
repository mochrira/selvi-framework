<?php 

namespace Selvi;

use Selvi\Database\Attributes\BelongsTo;
use Selvi\Database\Attributes\Column;

class ModelProperty {

    public string $type;
    public ?Column $column = null;
    public ?BelongsTo $relation = null;

}