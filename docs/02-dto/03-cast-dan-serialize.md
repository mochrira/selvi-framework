---
id: data-object-cast-serialize
title: Cast dan Serialize
type: panduan
tags: [dto, dataobject, cast, serialize, arraycast, datetimecast, converter]
prerequisites: [data-object]
verified: 2026-11-01
applies_to: Selvi 3.x
---

# Cast dan Serialize

`DataProperty` punya dua hook konversi:

- **`cast`** — mengubah nilai saat **dibaca** dari sumber (`of()`) dan saat
  dikirim ke database (`Converter::set()`).
- **`serialize`** — mengubah nilai saat **ditulis** ke output (`toArray()`).

Tanpa keduanya, `DataObject` memilih cast bawaan berdasarkan tipe properti.

## Jawaban singkat

```php
class PengaturanDTO extends DataObject {

    #[DataProperty('opsi', cast: new ArrayCast())]
    public ?array $opsi;

    #[DataProperty('diperbarui', serialize: new DateTimeCast(format: DATE_ATOM))]
    public DateTime $diperbarui;

}
```

`cast` berlaku saat dibaca dari sumber dan saat ditulis ke database;
`serialize` hanya berlaku saat `toArray()`.

## Prasyarat

- [Data Object (DTO)](01-data-object.md) sudah dipahami.

## 1. Cast bawaan

`Converter` memilih cast dengan urutan: `cast` eksplisit → cast terdaftar lewat
`register()` → tipe skalar → introspeksi kelas → tanpa cast.

| Tipe properti | Cast bawaan | Contoh input → nilai properti |
|---|---|---|
| `int` | `IntCast` | `'1'` → `1` |
| `float` | `FloatCast` | `'1.5'` → `1.5` |
| `bool` | `BoolCast` | `'0'` → `false` |
| `string` | `StringCast` | `1` → `'1'` |
| `BackedEnum` | `EnumCast` | `'aktif'` → `Status::Aktif` |
| `DateTimeInterface` | `DateTimeCast` | `'2026-11-01 09:00:00'` → `DateTimeImmutable` |

Cast bawaan dipilih dari tipe properti tanpa konfigurasi tambahan:

```php
class CatatanDTO extends DataObject {

    #[DataProperty('id')]
    public int $id;

    #[DataProperty('dibuat')]
    public DateTimeImmutable $dibuat;

}
```

```php
$dto = CatatanDTO::of((object) [
    'id' => '1',
    'dibuat' => '2026-11-01 09:00:00',
]);

return jsonResponse($dto->toArray());
```

Hasil:

```json
{
    "id": 1,
    "dibuat": "2026-11-01 09:00:00"
}
```

`DateTimeCast` memakai format `Y-m-d H:i:s` untuk `set()` dan `serialize()`.
Tipe `DateTimeInterface` menghasilkan `DateTimeImmutable`; tipe konkret seperti
`DateTimeImmutable` dipertahankan.

## 2. Cast eksplisit untuk kolom JSON

`ArrayCast` mengubah string JSON dari database menjadi array PHP. Cast ini
sengaja tidak menjadi bawaan karena `null` dan array kosong tidak bisa ditebak
dari tipe kolom.

```php
use Selvi\Database\Casting\ArrayCast;

class PengaturanDTO extends DataObject {

    #[DataProperty('id')]
    public int $id;

    #[DataProperty('opsi', cast: new ArrayCast())]
    public ?array $opsi;

}
```

```php
$dto = PengaturanDTO::of((object) [
    'id' => 1,
    'opsi' => '{"tema":"gelap","notifikasi":true}',
]);

return jsonResponse($dto->toArray());
```

Hasil:

```json
{
    "id": 1,
    "opsi": {
        "tema": "gelap",
        "notifikasi": true
    }
}
```

`ArrayCast` juga mengimplementasikan `ISerializesCast`, jadi `toArray()`
memakai cast yang sama untuk output. JSON tidak valid menghasilkan `null`,
bukan exception.

## 3. Serializer khusus untuk output

Untuk memakai format output berbeda dari format simpan, isi `serialize`.
Contoh `DATE_ATOM`:

```php
use Selvi\Database\Casting\DateTimeCast;

class JadwalDTO extends DataObject {

    #[DataProperty('diperbarui', serialize: new DateTimeCast(format: DATE_ATOM))]
    public DateTimeImmutable $diperbarui;

}
```

```php
$dto = JadwalDTO::of((object) [
    'diperbarui' => '2026-11-01 10:30:00',
]);

return jsonResponse($dto->toArray());
```

Hasil:

```json
{
    "diperbarui": "2026-11-01T10:30:00+00:00"
}
```

Nilai tetap dibaca memakai cast bawaan tipe (`DateTimeImmutable`), lalu
diformat ulang hanya saat `toArray()`.

## 4. Nilai `null`

Cast bawaan dan `ArrayCast` melewatkan `null` apa adanya. Aplikasi uji memakai
satu DTO dengan keempat properti; bila `opsi`, `dibuat`, dan `diperbarui`
tidak ada di sumber, hasilnya:

```json
{
    "id": 2,
    "opsi": null,
    "dibuat": null,
    "diperbarui": null
}
```

## Referensi API

### `new DataProperty($name, $prefix, $optional, $cast, $serialize)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$cast` | `?Selvi\Database\Interfaces\ICast` | `null` | cast dua arah; menang atas cast bawaan |
| `$serialize` | `?Selvi\Database\Interfaces\ISerializesCast` | `null` | serializer output `toArray()`; menang atas cast |

### `Converter::get($type, $value, $cast)`

Membaca nilai sumber: `cast` eksplisit → cast terdaftar → cast bawaan tipe.

**Kembalian:** `mixed`.

### `Converter::serialize($type, $value, $cast, $serialize)`

Menulis nilai ke `toArray()`: serializer eksplisit → cast yang juga
`ISerializesCast` → `IArrayable::toArray()` → nilai apa adanya.

**Kembalian:** `mixed`.

Signature lengkap ada di [`DataProperty`](../99-referensi/15-data-property.md)
dan [`DataObject`](../99-referensi/14-data-object.md).

## Kesalahan umum

| Gejala | Penyebab | Perbaikan |
|---|---|---|
| `null` padahal kolom JSON berisi data | isi kolom bukan JSON valid | perbaiki data; `ArrayCast` sengaja tidak melempar exception |
| `TypeError: Cannot assign null to property ... of type array` | `ArrayCast` mengembalikan `null` untuk JSON tidak valid, properti non-nullable | jadikan properti nullable |
| Tanggal tampil sebagai `Y-m-d H:i:s` padahal ingin format lain | hanya `cast` yang diisi | tambahkan `serialize` dengan format yang diinginkan |
| Cast eksplisit tidak berjalan | tipe properti tidak cocok dengan nilai hasil cast | pastikan tipe properti menerima hasil `cast->get()` |

## Sumber

- `src/Data/DataProperty.php` — parameter `cast` dan `serialize`
- `src/Data/DataObject.php` — pemanggilan `Converter::get()` dan
  `Converter::serialize()`
- `src/Database/Casting/Converter.php` — urutan pemilihan cast dan serializer
- `src/Database/Casting/ArrayCast.php` dan
  `src/Database/Casting/DateTimeCast.php` — cast yang dipakai contoh
- `src/Database/Interfaces/ICast.php` dan
  `src/Database/Interfaces/ISerializesCast.php` — kontrak cast
- `tests/02.03-cast-dan-serialize/index.php` — aplikasi uji halaman ini

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 1 November 2026 lewat `http://localhost:8089/02.03-cast-dan-serialize/`
dengan PHP 8.4.14 (timezone `UTC`):

| Kasus | Hasil |
|---|---|
| `GET /02.03-cast-dan-serialize/` | `200`, `opsi` menjadi objek, `dibuat` berformat `Y-m-d H:i:s`, `diperbarui` berformat `DATE_ATOM` |
| `GET /02.03-cast-dan-serialize/null` | `200`, `opsi`, `dibuat`, dan `diperbarui` bernilai `null` |

Belum diuji: `EnumCast`, `FloatCast`, `BoolCast`, `StringCast`, cast lewat
`Converter::register()`, dan JSON tidak valid. Semuanya dijelaskan dari
`src/Database/Casting/`.
