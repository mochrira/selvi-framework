---
id: data-object
title: Data Object (DTO)
type: panduan
tags: [dto, dataobject, dataproperty, mapping, toarray, optional]
prerequisites: [instalasi]
verified: 2026-11-01
applies_to: Selvi 3.x
---

# Data Object (DTO)

`Selvi\Data\DataObject` memetakan objek biasa (`stdClass`, baris hasil query)
menjadi objek dengan properti bertipe. Bentuknya seperti DTO: kelasnya hanya
berisi properti dan attribute, tanpa query dan tanpa koneksi database.

Kelas ini bukan ORM. `Selvi\Model` mewarisinya, jadi aturan pemetaan yang sama
berlaku untuk model, tetapi halaman ini hanya memakai `DataObject` langsung.

## Prasyarat

- [Instalasi](../01-memulai/01-instalasi.md) sudah berhasil dan autoload aktif.
- PHP >= 8.1 (attribute dan typed property dipakai).
- Tidak perlu koneksi database.

## 1. Mendefinisikan DTO

```php
use Selvi\Data\DataObject;
use Selvi\Data\DataProperty;

class KontakDTO extends DataObject {

    #[DataProperty('id_kontak')]
    public int $idKontak;

    #[DataProperty('nm_kontak')]
    public ?string $nmKontak;

    #[DataProperty('nomor', optional: true)]
    public string $nomor;

    public string $catatan = 'bukan kolom';

}
```

- Argumen pertama `DataProperty` adalah **nama kunci sumber** (`id_kontak`),
  bukan nama properti. Nama properti (`idKontak`) dipakai sebagai kunci output.
- Properti tanpa `DataProperty` (contoh `$catatan`) tidak ikut dipetakan.
- Semua properti harus punya type hint. Tipe itu dipakai untuk memilih cast.

## 2. Mengisi DTO dari objek (`of()`)

```php
$dto = KontakDTO::of((object) [
    'id_kontak' => '1',
    'nm_kontak' => 'Halo',
]);

return jsonResponse($dto->toArray());
```

Hasil:

```json
{
    "idKontak": 1,
    "nmKontak": "Halo"
}
```

`'1'` menjadi `1` karena tipe properti `int` memakai `IntCast` bawaan.

## 3. Membaca DTO sebagai array (`toArray()`)

`toArray()` mengembalikan array dengan **nama properti** sebagai kunci, lalu
menyerialkan setiap nilai sesuai tipe, cast, dan serializer properti.

```php
$dto = KontakDTO::of((object) [
    'id_kontak' => 2,
    'nm_kontak' => 'Budi',
    'nomor' => '08123',
]);

return jsonResponse($dto->toArray());
```

Hasil:

```json
{
    "idKontak": 2,
    "nmKontak": "Budi",
    "nomor": "08123"
}
```

## 4. Properti yang tidak ada di sumber

Perilakunya bergantung pada nullability dan opsi `optional`:

| Keadaan properti | Sumber tidak memuat kunci | Hasil di `toArray()` |
|---|---|---|
| `?string $nmKontak` (nullable) | diisi `null` saat `of()` | kunci ada, nilai `null` |
| `int $idKontak` (non-nullable) | dibiarkan **tidak terinisialisasi** | kunci ada, nilai `null` |
| `string $nomor` + `optional: true` | dibiarkan **tidak terinisialisasi** | kunci **dihilangkan** |

Contoh `optional: true`:

```php
$dto = KontakDTO::of((object) [
    'id_kontak' => 3,
    'nm_kontak' => 'Tanpa Nomor',
]);

return jsonResponse($dto->toArray());
```

Hasil:

```json
{
    "idKontak": 3,
    "nmKontak": "Tanpa Nomor"
}
```

`optional` hanya berpengaruh saat properti tidak terinisialisasi. Properti
nullable yang tidak ada di sumber tetap diisi `null`, sehingga kuncinya tetap
muncul walau `optional: true`.

## 5. Properti non-nullable yang hilang

`toArray()` tetap aman (mengembalikan `null`), tetapi membaca properti itu
langsung akan melempar `Error`:

```php
$dto = KontakWajibDTO::of((object) []);
isset($dto->idKontak);  // false
$dto->idKontak;         // Error
```

Pesan: `Typed property KontakWajibDTO::$idKontak must not be accessed before
initialization`.

## Referensi API

### `DataObject::of($item, $prefix)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$item` | `object` | wajib | sumber data; biasanya `stdClass` atau baris query |
| `$prefix` | `?string` | `''` | prefix jalur untuk properti bersarang; dipakai internal saat rekursi |

**Kembalian:** instance kelas yang memanggil (`static`).

### `DataObject::toArray()`

**Kembalian:** `array<string, mixed>` — kunci berupa nama properti, bukan nama
kolom sumber.

### `new DataProperty($name, $prefix, $optional, $cast, $serialize)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | `''` | kunci sumber; kosong hanya masuk akal bila `$prefix` diisi |
| `$prefix` | `?string` | `null` | prefix jalur untuk properti bersarang, lihat [properti bersarang](02-properti-nested.md) |
| `$optional` | `?bool` | `false` | bila `true`, properti yang tidak terinisialisasi dilewati `toArray()` |
| `$cast` | `?ICast` | `null` | cast khusus untuk membaca/menulis nilai |
| `$serialize` | `?ISerializesCast` | `null` | serializer khusus untuk output `toArray()` |

Signature lengkap ada di [`DataProperty`](../99-referensi/15-data-property.md)
dan [`DataObject`](../99-referensi/14-data-object.md).

## Kesalahan umum

| Gejala | Penyebab | Perbaikan |
|---|---|---|
| `Attribute "DataProperty" not found` | `use Selvi\Data\DataProperty;` belum ada | tambahkan import |
| Kunci output tidak muncul | properti tidak punya `DataProperty`, atau `optional: true` dan sumber tidak memuat kuncinya | beri attribute; hapus `optional` bila kunci harus selalu ada |
| Semua nilai `null` | nama kunci sumber salah (mis. `nama` vs `nm_kontak`) | samakan argumen `name` dengan kunci objek sumber |
| `Typed property ... must not be accessed before initialization` | properti non-nullable tidak ada di sumber lalu dibaca langsung | jadikan nullable, beri `optional: true`, atau sediakan kunci sumbernya |

## Sumber

- `src/Data/DataObject.php` — `of()`, `toArray()`, pembacaan attribute
- `src/Data/DataProperty.php` — definisi attribute
- `src/Interfaces/IDataObject.php` dan `src/Interfaces/IArrayable.php` — kontrak
  yang diimplementasikan
- `src/Database/Casting/Converter.php` — pemilihan cast dan serializer
- `tests/02.01-data-object/index.php` — aplikasi uji halaman ini

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 1 November 2026 lewat `http://localhost:8089/02.01-data-object/` dengan
PHP 8.4.14:

| Kasus | Hasil |
|---|---|
| `GET /02.01-data-object/` | `200`, `{"idKontak": 1, "nmKontak": "Halo"}`, `'1'` menjadi `1`, `nomor` hilang karena `optional` |
| `GET /02.01-data-object/lengkap` | `200`, tiga kunci termasuk `nomor` |
| `GET /02.01-data-object/wajib-hilang` | `200`, `toArray.idKontak` bernilai `null` dan `inisialisasi` bernilai `false` |

Pesan `Typed property ... must not be accessed before initialization` diuji
langsung lewat CLI. Perilaku `optional` pada properti nullable disimpulkan dari
`DataObject::of()` dan `toArray()`: `of()` mengisi `null`, sehingga
`toArray()` tidak pernah melihat properti itu sebagai tidak terinisialisasi.
