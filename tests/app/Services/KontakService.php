<?php 

namespace Selvi\Tests\Services;

use Selvi\Collection;
use Selvi\Database\DB;
use Selvi\Database\Interfaces\IQueryBuilder;
use Selvi\Tests\DTO\KontakDTO;

class KontakService {

    function mainQuery(): IQueryBuilder {
        $a = DB::table('unit')
            ->leftJoin('departemen', 'departemen.id_departemen = unit.id_departemen')
            ->select(
                'unit.id_unit',
                'unit.id_unit AS unit__id_unit',
                'unit.nm_unit AS unit__nm_unit',
                'unit.id_departemen AS unit__id_departemen',
                'departemen.id_departemen AS unit_departemen__id_departemen',
                'departemen.nm_departemen AS unit_departemen__nm_departemen'
            )
            ->getSql();
        
        $b = DB::table('profesi')
            ->leftJoin('unit', 'unit.id_unit = profesi.id_unit')
            ->leftJoin('departemen', 'departemen.id_departemen = unit.id_departemen')
            ->select(
                'profesi.id_profesi',
                'profesi.id_profesi AS profesi__id_profesi',
                'profesi.nm_profesi AS profesi__nm_profesi',
                'profesi.id_unit AS profesi__id_unit',
                'unit.id_unit AS profesi_unit__id_unit',
                'unit.nm_unit AS profesi_unit__nm_unit',
                'unit.id_departemen AS profesi_unit__id_departemen',
                'departemen.id_departemen AS profesi_unit_departemen__id_departemen',
                'departemen.nm_departemen AS profesi_unit_departemen__nm_departemen'
            )
            ->getSql();

        return DB::table('kontak')
            ->leftJoin('departemen', 'departemen.id_departemen = kontak.id_departemen')
            ->leftJoin("({$a}) unit", 'unit.unit__id_unit = kontak.id_unit')
            ->leftJoin("({$b}) profesi", 'profesi.id_profesi = kontak.id_profesi')
            ->select(
                'kontak.id_kontak AS id_kontak',
                'kontak.nm_kontak AS nm_kontak',
                'kontak.id_departemen AS id_departemen',
                'departemen.id_departemen AS departemen__id_departemen',
                'departemen.nm_departemen AS departemen__nm_departemen',
                'unit.*',
                'profesi.*');
    }

    function result() {
        $result = $this->mainQuery()->get()->rows();
        return Collection::fromMap(fn ($item) => KontakDTO::of($item), $result);
    }

    function row(mixed $where) {
        $row = $this->mainQuery()->where($where)->get()->row();
        return KontakDTO::of($row);
    }

}