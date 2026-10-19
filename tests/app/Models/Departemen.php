<?php 

namespace Selvi\Tests\Models;

use Selvi\Database\Builder\DML\QueryBuilder;
use Selvi\Database\DB;
use Selvi\Model;
use Selvi\Model\Attributes\Aggregate;
use Selvi\Model\Attributes\Column;
use Selvi\Model\Attributes\Table;
use Selvi\Tests\Casting\ZeroIfNullCast;

#[Table('departemen', 'main')]
class Departemen extends Model {

    #[Column('id_departemen', key: true)]
    public int $idDepartemen;

    #[Column('nm_departemen')]
    public ?string $nmDepartemen = null;

    #[Aggregate('summary_kontak', key: 'id_departemen', column: 'jml_kontak', serialize: new ZeroIfNullCast())]
    public ?int $jmlKontak = null;

    #[Aggregate('summary_sales', key: 'id_departemen', column: 'sales_total', serialize: new ZeroIfNullCast())]
    public ?int $totalSales = null;

    #[Aggregate('summary_sales', key: 'id_departemen', column: 'sales_max', serialize: new ZeroIfNullCast())]
    public ?int $maxSales = null;

    #[Aggregate('summary_sales', key: 'id_departemen', column: 'sales_min', serialize: new ZeroIfNullCast())]
    public ?int $minSales = null;

    #[Aggregate('summary_sales', key: 'id_departemen', column: 'sales_avg', serialize: new ZeroIfNullCast())]
    public ?int $avgSales = null;

    public static function summary_sales(): QueryBuilder {
        return DB::table('transaksi')
            ->innerJoin('kontak', 'kontak.id_kontak = transaksi.id_kontak')
            ->select(
                'kontak.id_departemen',
                ['SUM(transaksi.sales)', 'sales_total'],
                ['MAX(transaksi.sales)', 'sales_max'],
                ['MIN(transaksi.sales)', 'sales_min'],
                ['AVG(transaksi.sales)', 'sales_avg'])
            ->group('kontak.id_departemen');
    }

    public static function summary_kontak(): QueryBuilder {
        return DB::table('kontak')
            ->select(
                'kontak.id_departemen',
                ['COUNT(kontak.id_kontak)', 'jml_kontak'])
            ->group('kontak.id_departemen');
    }

}