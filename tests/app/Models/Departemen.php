<?php 

namespace Selvi\Tests\Models;

use Selvi\Database\Builder\DML\QueryBuilder;
use Selvi\Database\DB;
use Selvi\Model;
use Selvi\Model\Attributes\Aggregate;
use Selvi\Model\Attributes\Column;
use Selvi\Model\Attributes\Table;

#[Table('departemen', 'main')]
class Departemen extends Model {

    #[Column('id_departemen', key: true)]
    public int $idDepartemen;

    #[Column('nm_departemen')]
    public ?string $nmDepartemen = null;

    #[Aggregate('summary', key: 'id_departemen', column: 'jml_kontak')]
    public ?int $jmlKontak = null;

    public static function summary(): QueryBuilder {
        return DB::table('kontak')->select(
            'kontak.id_departemen',
            ['COUNT(kontak.id_kontak)', 'jml_kontak']
        )
        ->group(
            'kontak.id_departemen'
        );
    }

}