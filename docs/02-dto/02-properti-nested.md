---
id: data-object-nested
title: Properti Bersarang
type: panduan
tags: [dto, dataobject, nested, prefix, join, dataproperty, optional, nullable]
prerequisites: [data-object]
verified: 2026-11-01
applies_to: Selvi 3.x
---

# Properti Bersarang

Hasil query dengan `JOIN` biasanya datar: kolom tabel induk dan tabel anak
berada di satu baris. `DataProperty(prefix: ...)` mengubah baris datar itu
menjadi properti DTO bersarang, tanpa query tambahan.

Ada dua bentuk sumber yang didukung: kunci berprefix (`departemen__id_departemen`)
dan objek yang sudah bersarang (`$row->grup` berupa objek).

## Jawaban singkat

```php
class KontakDTO extends DataObject {

    #[DataProperty(prefix: 'departemen', optional: true)]
    public ?DepartemenDTO $departemen;

}

$dto = KontakDTO::of((object) [
    'departemen__id_departemen' => 2,
    'departemen__nm_departemen' => 'Sales',
]);

$dto->departemen->nmDepartemen; // 'Sales'
```

Kunci berprefix `{prefix}__{name}` dipetakan ke properti DTO bersarang.

## Prasyarat

- [Data Object (DTO)](01-data-object.md) sudah dipahami.

## 1. Menandai properti bersarang

```php
use Selvi\Data\DataObject;
use Selvi\Data\DataProperty;

class DepartemenDTO extends DataObject {

    #[DataProperty('id_departemen')]
    public int $idDepartemen;

    #[DataProperty('nm_departemen')]
    public ?string $nmDepartemen;

}

class KontakDTO extends DataObject {

    #[DataProperty('id_kontak')]
    public int $idKontak;

    #[DataProperty('nm_kontak')]
    public ?string $nmKontak;

    #[DataProperty(prefix: 'departemen', optional: true)]
    public DepartemenDTO $departemen;

    #[DataProperty(prefix: 'unit', optional: true)]
    public UnitDTO $unit;

}
```

`UnitDTO` ditunjukkan pada bagian [dua tingkat](#3-membaca-dua-tingkat).
`name` boleh dikosongkan bila `prefix` diisi. Nama properti (`$departemen`)
tetap dipakai sebagai kunci output.

`optional: true` dipakai agar relasi yang tidak tersedia tidak ikut ke
`toArray()`. Konsekuensinya properti itu bisa belum terinisialisasi; lihat
[bagian 6](#6-nullable-atau-optional).

## 2. Membaca satu tingkat

```php
$dto = KontakDTO::of((object) [
    'id_kontak' => 1,
    'nm_kontak' => 'Halo',
    'departemen__id_departemen' => 2,
    'departemen__nm_departemen' => 'Sales',
]);

return jsonResponse($dto->toArray());
```

Hasil:

```json
{
    "idKontak": 1,
    "nmKontak": "Halo",
    "departemen": {
        "idDepartemen": 2,
        "nmDepartemen": "Sales"
    }
}
```

Kunci sumber berbentuk **`{prefix}__{name}`** (dua garis bawah). Prefix yang
diteruskan ke pemanggilan bersarang sudah berakhir dengan `_`, lalu `of()`
menambahkan `_` sebelum nama kolom.

## 3. Membaca dua tingkat

Prefix bertingkat mengikuti nama properti induknya:

```php
class UnitDTO extends DataObject {

    #[DataProperty('id_unit')]
    public int $idUnit;

    #[DataProperty('nm_unit')]
    public ?string $nmUnit;

    #[DataProperty(prefix: 'departemen')]
    public ?DepartemenDTO $departemen;

}
```

Sumber:

```php
$dto = KontakDTO::of((object) [
    'id_kontak' => 3,
    'nm_kontak' => 'Budi',
    'unit__id_unit' => 4,
    'unit__nm_unit' => 'Marketing',
    'unit_departemen__id_departemen' => 2,
    'unit_departemen__nm_departemen' => 'Sales',
]);
```

Hasil:

```json
{
    "idKontak": 3,
    "nmKontak": "Budi",
    "unit": {
        "idUnit": 4,
        "nmUnit": "Marketing",
        "departemen": {
            "idDepartemen": 2,
            "nmDepartemen": "Sales"
        }
    }
}
```

Jalur kunci mengikuti jalur properti: `unit` → `unit__...`, lalu
`unit` + `departemen` → `unit_departemen__...`.

## 4. Objek yang sudah bersarang

Bila sumber sudah berisi objek, tidak perlu `prefix`. Cukup beri `DataProperty`
dengan nama kunci objeknya:

```php
use Selvi\Data\DataObject;
use Selvi\Data\DataProperty;

class GrupDTO extends DataObject {

    #[DataProperty('id_grup')]
    public int $idGrup;

    #[DataProperty('nm_grup')]
    public ?string $nmGrup;

}

class KontakGrupDTO extends DataObject {

    #[DataProperty('id_kontak')]
    public int $idKontak;

    #[DataProperty('grup')]
    public ?GrupDTO $grup;

}
```

```php
$grup = new stdClass();
$grup->id_grup = 5;
$grup->nm_grup = 'Mitra';

$dto = KontakGrupDTO::of((object) [
    'id_kontak' => 6,
    'grup' => $grup,
]);
```

Hasil:

```json
{
    "idKontak": 6,
    "grup": {
        "idGrup": 5,
        "nmGrup": "Mitra"
    }
}
```

`of()` memanggil `GrupDTO::of()` otomatis karena tipe properti adalah turunan
`DataObject`.

Bila kunci `grup` ada tetapi nilainya `null`, hasilnya mengikuti nullability
properti:

- `?GrupDTO` → diisi `null`, output `"grup": null`;
- `GrupDTO` → `TypeError: Cannot assign null to property ... of type GrupDTO`.

`optional` tidak menelan `null` eksplisit; ia hanya melewati properti yang
belum terinisialisasi.

## 5. Relasi tidak lengkap

Prefix dianggap ada bila **minimal satu** kunci berawalan `{jalur}_` bernilai
tidak `null`. Bila tidak ada, hasilnya bergantung pada deklarasi properti:

- `optional: true` → kunci **dihilangkan** dari `toArray()` (properti tidak
  terinisialisasi);
- nullable tanpa `optional` → kunci muncul dengan nilai `null`;
- non-nullable tanpa `optional` → `toArray()` menampilkan `null`, tetapi
  properti tetap tidak terinisialisasi.

```php
$dto = KontakDTO::of((object) [
    'id_kontak' => 7,
    'nm_kontak' => 'Tanpa Relasi',
]);
```

Hasil:

```json
{
    "idKontak": 7,
    "nmKontak": "Tanpa Relasi"
}
```

## 6. Nullable atau optional?

`optional` dan tanda `?` menyelesaikan dua masalah berbeda:

| Deklarasi | Relasi tidak ada | `toArray()` | Akses `$dto->unit` |
|---|---|---|---|
| `UnitDTO` + `optional: true` | properti tidak terinisialisasi | kunci hilang | `Error` |
| `?UnitDTO` | diisi `null` oleh `of()` | `"unit": null` | `null` |
| `?UnitDTO` + `optional: true` | diisi `null` oleh `of()` | `"unit": null` | `null` |
| `UnitDTO` tanpa `optional` | tidak terinisialisasi | `"unit": null` | `Error` |

- Pilih **`optional: true`** bila kontrak output memang boleh tidak memuat
  kunci relasi. Semua akses langsung harus dijaga `isset()`.
- Pilih **nullable tanpa `optional`** bila konsumen butuh bentuk output stabil
  (kunci selalu ada). Ini pilihan yang lebih aman untuk relasi objek.
- `optional` tidak memfilter nilai `null`; ia hanya melewati properti yang
  belum terinisialisasi.

## Referensi API

### `new DataProperty($name, $prefix, $optional, $cast, $serialize)`

Untuk properti bersarang, isi `$prefix` dan biarkan `$name` kosong. Properti
harus bertipe turunan `DataObject`; bila tidak, cabang prefix dilewati dan
pemetaan jatuh ke pencarian `$name` biasa.

`$optional` hanya berpengaruh pada properti yang belum terinisialisasi.
Properti nullable yang tidak ada di sumber sudah diisi `null` oleh `of()`,
sehingga tetap muncul di `toArray()` walau `optional: true`.

Signature lengkap ada di [`DataProperty`](../99-referensi/15-data-property.md)
dan [`DataObject`](../99-referensi/14-data-object.md).

## Kesalahan umum

| Gejala | Penyebab | Perbaikan |
|---|---|---|
| Properti bersarang `null` atau tidak muncul | kunci memakai satu garis bawah (`departemen_id_departemen`) | pakai `{prefix}__{name}` |
| Properti bersarang `null` atau tidak muncul | semua kunci berprefix bernilai `null` | pastikan minimal satu kolom anak terisi |
| Anak dari cucu tidak terbaca | prefix anak ditulis tanpa jalur induk (`departemen` padahal berada di `unit`) | `of()` sudah menambahkan jalur induk otomatis; cukup tulis prefix lokal (`departemen`) |
| `optional: true` tidak membuat kunci hilang | properti nullable sudah diisi `null` oleh `of()` | jadikan non-nullable, atau filter di layer output |
| `TypeError: Cannot assign null to property ...` | sumber berisi `null` untuk properti DTO non-nullable | jadikan nullable, atau perbaiki data sumber |
| `Typed property ... must not be accessed before initialization` | properti non-nullable tidak terisi (dengan atau tanpa `optional`) lalu diakses langsung | jadikan nullable, atau jaga akses dengan `isset()` |

Catatan: pencocokan prefix memakai awalan jalur, jadi `unit_` juga cocok
dengan `unit_departemen__...`. Bila hanya kolom cucu yang ada, induk `unit`
tetap dibentuk dan anaknya berisi nilai dari kolom cucu tersebut.

## Sumber

- `src/Data/DataObject.php` — orchestrator `of()` dan pemanggilan `assign()`
- `src/Data/DataRow.php` — pembacaan prefix dan `hasAny()`
- `src/Data/DataProperty.php` — cabang `assign()` dengan prefix dan
  pemanggilan `of()` rekursif
- `src/Data/DataProperty.php` — parameter `prefix`
- `tests/02.02-properti-nested/index.php` — aplikasi uji halaman ini
- `tests/app/DTO/KontakDTO.php` — contoh pemakaian pada aplikasi harness

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 1 November 2026 lewat `http://localhost:8089/02.02-properti-nested/`
dengan PHP 8.4.14:

| Kasus | Hasil |
|---|---|
| `GET /02.02-properti-nested/` | `200`, `departemen` terisi, `unit` tidak muncul |
| `GET /02.02-properti-nested/dua-tingkat` | `200`, `unit.departemen` terisi dari kunci `unit_departemen__...`, `departemen` root tidak muncul |
| `GET /02.02-properti-nested/objek-bersarang` | `200`, objek `grup` dipetakan menjadi `GrupDTO` |
| `GET /02.02-properti-nested/objek-null` | `200`, `"grup": null` pada properti nullable |
| `GET /02.02-properti-nested/objek-null-wajib` | `500`, `TypeError: Cannot assign null to property KontakGrupWajibDTO::$grup of type GrupDTO` |
| `GET /02.02-properti-nested/tanpa-anak` | `200`, kedua properti bersarang tidak muncul |
| CLI: `?GrupDTO $grup` dengan prefix `grup` tanpa data | `toArray()` menghasilkan `{"grup":null}` |

Perilaku "minimal satu kunci tidak `null`" disimpulkan dari
`DataRow::hasAny()`; kasus semua kunci berprefix bernilai `null` belum diuji.
