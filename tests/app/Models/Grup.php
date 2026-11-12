<?php

declare(strict_types=1);

namespace Selvi\Tests\Models;

use Selvi\Model;
use Selvi\Model\Attributes\BelongsTo;
use Selvi\Model\Attributes\Column;
use Selvi\Model\Attributes\Table;

#[Table('grup', 'main')]
class Grup extends Model {

    #[Column('id_grup', key: true)]
    public int $idGrup;

    #[Column('nm_grup')]
    public ?string $nmGrup = null;

    #[Column('parent_id')]
    public ?int $parentId = null;

    #[BelongsTo(Grup::class, foreignKey: 'parent_id')]
    public ?Grup $parent = null;

}
