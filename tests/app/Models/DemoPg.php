<?php

namespace Selvi\Tests\Models;

use Selvi\Model;
use Selvi\Model\Attributes\Column;
use Selvi\Model\Attributes\Table;

#[Table('demo_pgsql', 'pgdemo')]
class DemoPg extends Model {

    #[Column('id_kontak', key: true)]
    public int $idKontak;

    #[Column('nm_kontak')]
    public ?string $nmKontak = null;

    #[Column('aktif')]
    public ?bool $aktif = null;

}
