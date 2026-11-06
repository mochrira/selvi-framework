---
id: model-konversi-value
title: Konversi Value
type: panduan
tags: [model, cast, serialize, converter, arraycast, datetimecast, enumcast]
prerequisites: [model]
verified: 2026-11-01
applies_to: Selvi 3.x
---

# Konversi Value

`Selvi\Database\Casting\Converter` mengubah nilai pada tiga titik:

| Method | Arah |
|---|---|
| `get()` | database/sumber → properti |
| `set()` | properti → database |
| `serialize()` | properti → `toArray()`/JSON |

`Column` bisa memberi `cast` (dua arah) dan `serialize` (khusus output). Bila
tidak diisi, Converter memilih cast berdasarkan tipe properti.

## Jawaban singkat

```php
#[Column('opsi', cast: new ArrayCast())]
public ?array $opsi;

#[Column('created_at', serialize: new DateTimeCast(format: DATE_ATOM))]
public DateTime $createdAt;
```

Tanpa `cast`/`serialize`, Converter memilih cast dari tipe properti: `int`,
`float`, `bool`, `string`, `DateTimeInterface`, dan `BackedEnum`.

## Prasyarat

- [Model](01-model.md) sudah dipahami.

## 1. Cast bawaan

| Tipe properti | Cast | Contoh |
|---|---|---|
| `int` | `IntCast` | `'12'` → `12` |
| `float` | `FloatCast` | `'1.5'` → `1.5` |
| `bool` | `BoolCast` | `'0'` → `false` |
| `string` | `StringCast` | `10` → `'10'` |
| `BackedEnum` | `EnumCast` | `'aktif'` → `StatusDemo::Aktif` |
| `DateTimeInterface` | `DateTimeCast` | `'2026-11-01 09:00:00'` → `DateTime` |

```php
$converter = Converter::instance();

$converter->get('int', '12');       // 12
$converter->get('bool', '0');       // false
$converter->get('float', '1.5');    // 1.5
$converter->get('string', 10);      // '10'
$converter->set('int', '12');       // '12'
$converter->serialize('int', 12);   // 12
```

`set()` mengembalikan nilai siap simpan (bukan literal SQL). Sanitasi literal
dilakukan Grammar.

## 2. `ArrayCast`

Untuk kolom JSON:

```php
use Selvi\Database\Casting\ArrayCast;

#[Column('opsi', cast: new ArrayCast())]
public ?array $opsi;
```

Perilaku cast:

| Operasi | Hasil |
|---|---|
| `get('{"tema":"gelap"}')` | `['tema' => 'gelap']` |
| `get('bukan json')` | `null` |
| `set(['a' => 1])` | `'{"a":1}'` |
| `serialize([['a' => 1]])` | `[['a' => 1]]` |

`ArrayCast` sengaja tidak menjadi cast bawaan karena `null` dan array kosong
tidak bisa ditebak dari tipe kolom. JSON tidak valid menghasilkan `null`, bukan
exception.

## 3. `DateTimeCast`

```php
use Selvi\Database\Casting\DateTimeCast;

#[Column('created_at', serialize: new DateTimeCast(format: DATE_ATOM))]
public ?DateTime $createdAt;
```

Hasil pada pengujian:

| Operasi | Hasil |
|---|---|
| `get('2026-11-01 09:00:00')` | `DateTime` |
| `set(DateTime)` | `'2026-11-01 09:00:00'` |
| `serialize(DateTime)` | `'2026-11-01 09:00:00'` |
| `serialize` dengan `DATE_ATOM` | `'2026-11-01T09:00:00+00:00'` |

Default format adalah `Y-m-d H:i:s`.

## 4. `EnumCast`

```php
enum StatusDemo: string {
    case Aktif = 'aktif';
    case Nonaktif = 'nonaktif';
}

Converter::instance()->get(StatusDemo::class, 'aktif'); // StatusDemo::Aktif
Converter::instance()->serialize(StatusDemo::class, StatusDemo::Nonaktif); // 'nonaktif'
```

Enum cast dipilih otomatis dari tipe properti `BackedEnum`.

## 5. Cast kustom lewat `register()`

```php
use Selvi\Database\Interfaces\ICast;
use Selvi\Database\Interfaces\ISerializesCast;

final class UangCast implements ICast, ISerializesCast {

    public function get(mixed $value): mixed {
        return $value === null ? null : (int) $value;
    }

    public function set(mixed $value): mixed {
        return $value === null ? null : (int) $value;
    }

    public function serialize(mixed $value): mixed {
        return 'Rp '.number_format((int) $value, 0, ',', '.');
    }

}

Converter::instance()->register('uang', new UangCast());
```

Hasil:

| Operasi | Hasil |
|---|---|
| `get('uang', '1500')` | `1500` |
| `set('uang', 1500)` | `1500` |
| `serialize('uang', 1500)` | `'Rp 1.500'` |

`register()` menang atas cast bawaan dan membersihkan cache tipe tersebut.

## 6. Integrasi dengan Model

```php
#[Table('kontak', 'main')]
class KontakCast extends Model {

    #[Column('id_kontak', key: true)]
    public int $idKontak;

    #[Column('nm_kontak')]
    public string $nmKontak;

    #[Column('created_at')]
    public ?DateTime $createdAt;

}
```

`KontakCast::find(5)` menghasilkan:

```json
{
    "toArray": {
        "idKontak": 5,
        "nmKontak": "John Doe",
        "createdAt": "2026-10-21 04:46:48"
    },
    "toArrayDb": {
        "id_kontak": 5,
        "nm_kontak": "John Doe",
        "created_at": "2026-10-21 04:46:48"
    }
}
```

- `get()` dipakai saat `of()` memetakan hasil query.
- `serialize()` dipakai `toArray()`.
- `set()` dipakai `toArrayDb()` sebelum `insert()`/`update()`.

## Referensi API

- [`Converter`](../99-referensi/29-converter.md) — `get()`, `set()`,
  `serialize()`, `register()`.
- [`Column`](../99-referensi/25-column.md) — parameter `cast` dan `serialize`.
- [`Cast dan serialize`](../02-dto/03-cast-dan-serialize.md) — konversi pada
  DTO.

## Kesalahan umum

| Gejala | Penyebab | Perbaikan |
|---|---|---|
| `ArrayCast` menghasilkan `null` | isi kolom bukan JSON valid | perbaiki data; cast tidak melempar exception |
| `TypeError: Cannot assign null to property ... of type array` | JSON tidak valid pada properti non-nullable | jadikan nullable |
| Tanggal memakai format simpan | hanya `cast` yang diisi | tambahkan `serialize` dengan `DateTimeCast(format: ...)` |
| Cast kustom tidak dipakai | tipe properti tidak cocok / lupa `register()` | daftarkan sebelum query |
| Nilai `null` berubah | cast seharusnya melewatkan `null` | pastikan implementasi `get()`/`set()` menangani `null` |

## Sumber

- [src/Database/Casting/Converter.php](../../src/Database/Casting/Converter.php)
- [src/Database/Casting/ArrayCast.php](../../src/Database/Casting/ArrayCast.php), `DateTimeCast.php`, `EnumCast.php`
- [src/Database/Interfaces/ICast.php](../../src/Database/Interfaces/ICast.php), `ISerializesCast.php`
- [src/Model/Attributes/Column.php](../../src/Model/Attributes/Column.php)
- [tests/03.03-konversi-value/index.php](../../tests/03.03-konversi-value/index.php) — aplikasi uji halaman ini

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 1 November 2026 lewat `http://localhost:8089/03.03-konversi-value/`
(PHP 8.4.14, timezone `UTC`):

| Endpoint | Hasil |
|---|---|
| `GET /03.03-konversi-value/dasar` | `200`, int/bool/float/string sesuai |
| `GET /03.03-konversi-value/array` | `200`, JSON valid/desimal, JSON rusak `null` |
| `GET /03.03-konversi-value/tanggal` | `200`, format simpan dan `DATE_ATOM` |
| `GET /03.03-konversi-value/enum` | `200`, enum dan nilai backing |
| `GET /03.03-konversi-value/custom` | `200`, `1500` dan `Rp 1.500` |
| `GET /03.03-konversi-value/model` | `200`, `toArray` dan `toArrayDb` |

Belum diuji: `IntCast`/`FloatCast`/`BoolCast`/`StringCast` secara langsung
(dipakai lewat `get()` pada endpoint `/dasar`), dan `register()` untuk
menimpa cast bawaan.
