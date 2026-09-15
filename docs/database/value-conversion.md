# Konversi Value (Database ↔ Model ↔ JSON)

Satu value melewati tiga titik konversi: saat **dibaca** dari database, saat
**disimpan** ke database, dan saat model **dikeluarkan** sebagai array/JSON.
Ketiganya ditangani oleh `Selvi\Database\Casting\Converter`, sehingga tidak ada
lagi pemetaan tipe yang ditulis ulang di dalam `Model`.

## Tiga Arah Konversi

| Arah | Method | Dipakai oleh |
|---|---|---|
| DB → model | `CastInterface::get()` | hidrasi `of()`, `create()`, `update()` |
| model → DB | `CastInterface::set()` | `toArrayDb()` (dipakai `update()`) |
| model → output | `SerializesCastInterface::serialize()` | `toArray()` / JSON |

`serialize()` bersifat **opsional**. Hanya cast yang bentuk PHP-nya tidak bisa
langsung di-encode yang membutuhkannya (DateTime dan enum); cast tipe skalar
cukup mengimplementasikan `CastInterface`.

## Cast Bukan Sanitizer

Ini pembagian yang harus dijaga saat menulis cast atau driver baru:

| Lapisan | Tugas | Sifat |
|---|---|---|
| `Cast` | value PHP ↔ value PHP | Netral driver |
| `Sanitizer` | value PHP → literal SQL | Spesifik driver |

Artinya: `set()` mengembalikan **value PHP biasa** (`'2026-09-14 09:00:00'`),
bukan literal SQL. Escaping dan quoting tetap dikerjakan `SanitizerInterface`
milik driver.

## Cast Bawaan

Cast dipilih dari **tipe property**, jadi model biasa tidak perlu konfigurasi apa
pun:

| Tipe property | Cast | `get()` | `set()` | `serialize()` |
|---|---|---|---|---|
| `int` | `IntCast` | `(int)` | apa adanya | — |
| `float` | `FloatCast` | `(float)` | apa adanya | — |
| `bool` | `BoolCast` | `(bool)` | apa adanya | — |
| `string` | `StringCast` | `(string)` | apa adanya | — |
| `BackedEnum` | `EnumCast` | `Enum::from()`, ikut backing type | `->value` | `->value` |
| `DateTimeInterface` | `DateTimeCast` | `new $type(string)` | `->format('Y-m-d H:i:s')` | `->format('Y-m-d H:i:s')` |
| `mixed`, tanpa tipe, union | — | apa adanya | apa adanya | apa adanya |

Kolom JSON (`array`) tidak masuk daftar ini karena butuh cast eksplisit — lihat
[Kolom JSON](#kolom-json).

Aturan lain yang berlaku untuk semua cast bawaan:

- **`null` tidak diubah.** Kolom `NULL` menjadi `null`, bukan `0` atau `''`.
  Cast kustom bebas menentukan lain (mis. `null` menjadi array kosong).
- Cast **wajib stateless**. Instance dibuat sekali saat attribute dibaca dan
  dipakai bersama semua baris.

Contoh tanpa konfigurasi apa pun:

```php
#[Column('createdAt')]
public ?DateTime $createdAt;

$kontak->createdAt;                  // objek DateTime (dari get())
$kontak->toArray()['createdAt'];     // "2026-09-14 09:00:00" (dari serialize())
```

## Urutan Pemilihan Cast

`Converter` mencari cast secara berurutan dan berhenti pada yang pertama cocok:

1. `cast` yang diisi eksplisit di attribute `Column`,
2. cast yang didaftarkan lewat `register()`,
3. cast bawaan untuk tipe skalar (`int`, `float`, `bool`, `string`),
4. introspeksi kelas (`BackedEnum`, `DateTimeInterface`),
5. tidak ada yang cocok → value dilewatkan apa adanya.

## Override Per Kolom

`Column` menerima dua slot opsional. Keduanya berisi **instance**, bukan nama
kelas, karena argumen attribute menerima `new`:

```php
#[Column(
    'createdAt',
    cast: new DateTimeCast(),
    serialize: new DateTimeCast(format: DATE_ATOM)
)]
public ?DateTime $createdAt;
```

Aturan kombinasinya:

| Situasi | Hasil |
|---|---|
| `cast` diisi | `get()`/`set()` memakai cast itu |
| `serialize` diisi | `serialize()` memakai serializer itu |
| `serialize` kosong, `cast` mengimplementasikan `SerializesCastInterface` | cast itu yang dipakai untuk output |
| keduanya kosong | cast dipilih otomatis dari tipe property |

## Cast Kustom

Implementasi cast mengikuti gaya Laravel: `get()` untuk DB → model dan `set()`
untuk model → DB.

```php
namespace App\Casts;

use Selvi\Database\Casting\CastInterface;
use Selvi\Database\Casting\SerializesCastInterface;

final class MoneyCast implements CastInterface, SerializesCastInterface {

    public function get(mixed $value): mixed {
        return $value === null ? null : Money::fromCents((int) $value);
    }

    public function set(mixed $value): mixed {
        return $value instanceof Money ? $value->cents() : $value;
    }

    public function serialize(mixed $value): mixed {
        return $value instanceof Money ? $value->formatted() : $value;
    }

}
```

Daftarkan sekali di bootstrap, lalu seluruh model bisa memakai tipe `Money`:

```php
use Selvi\Database\Casting\Converter;

Converter::instance()->register(Money::class, new MoneyCast());
```

`register()` menang atas cast bawaan, jadi tipe bawaan pun bisa ditimpa — mis.
semua kolom `string` di-trim:

```php
Converter::instance()->register('string', new TrimmedStringCast());
```

Bila cast kustom hanya boleh berlaku di satu model, timpa `converter()` di model
tersebut alih-alih memakai singleton:

```php
class Kontak extends Model {

    private static ?Converter $converter = null;

    static function converter() : Converter {
        return self::$converter ??= (new Converter())->register(Money::class, new MoneyCast());
    }
}
```

## Kolom JSON

Kolom JSON memakai `ArrayCast` secara eksplisit, karena bentuk hasilnya (array
kosong atau `null`) tidak bisa ditebak dari tipe kolom:

```php
#[Column('options', cast: new ArrayCast())]
public ?array $options;
```

## Hubungan dengan Laravel

Nama method sengaja mengikuti Eloquent supaya pengetahuan dari Laravel bisa
langsung dipakai:

| Laravel | Selvi |
|---|---|
| `CastsAttributes::get()` | `CastInterface::get()` |
| `CastsAttributes::set()` | `CastInterface::set()` |
| `SerializesCastableAttributes::serialize()` | `SerializesCastInterface::serialize()` |

Dua perbedaan yang disengaja:

- Laravel mendaftarkan cast per **atribut** (`casts()`), Selvi menurunkannya dari
  **tipe property** lebih dulu dan menyediakan `Column`/`register()` sebagai
  override.
- Format tanggal untuk penyimpanan dan untuk output belum dipisah seperti
  `$dateFormat` vs `serializeDate()` di Laravel; keduanya memakai `format` milik
  `DateTimeCast`.

## Belum Didukung

- Cast yang menggabungkan beberapa kolom menjadi satu value object (butuh peta
  kolom ke satu property, sedangkan Selvi memakai satu `#[Column]` per property).
- Cast untuk `create()`: data yang dikirim ke `insert()` masih diteruskan apa
  adanya, jadi value object kustom baru bisa ditulis lewat `update()`.
- `IncludeIfNull` dan opsi membuang kolom `null` dari `toArrayDb()`.
