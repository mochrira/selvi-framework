---
id: model
title: Model
type: panduan
tags: [model, database, table, column, crud, query, notmapped]
prerequisites: [instalasi, database-koneksi, database-skema-tabel]
verified: 2026-11-05
applies_to: Selvi 3.x
---

# Model

`Selvi\Model` memetakan baris database ke objek bertipe. Model mewarisi
[`DataObject`](../02-dto/01-data-object.md), jadi pemetaan `of()` dan
`toArray()` memakai aturan yang sama, ditambah metadata tabel/kolom lewat
attribute.

Halaman ini membahas model tanpa relasi. Relasi ada di
[relasi](02-relasi.md); cast dan serializer ada di
[konversi value](03-konversi-value.md).

## Jawaban singkat

```php
#[Table('kontak', schema: 'main')]
class Kontak extends Model {

    #[Column('id_kontak', key: true)]
    public int $idKontak;

    #[Column('nm_kontak')]
    public ?string $nmKontak;

}

$kontak = Kontak::find(1);   // null bila tidak ada
$kontak?->toArray();
```

Butuh koneksi `main` terdaftar dan tabel `kontak` sudah dibuat.

## Prasyarat

- [Instalasi](../01-memulai/01-instalasi.md) sudah berhasil.
- [Koneksi database](../04-database/01-koneksi.md) sudah terdaftar.
- Tabel sudah dibuat, misalnya lewat
  [skema tabel](../04-database/02-skema-tabel.md).

Halaman koneksi dan skema berada di bab `04-database/`; bab ini memakainya
sebagai prasyarat karena model selalu bekerja di atas koneksi yang terdaftar.

## 1. Mendefinisikan model

```php
use DateTime;
use Selvi\Model;
use Selvi\Model\Attributes\Column;
use Selvi\Model\Attributes\Table;

#[Table('kontak', schema: 'main')]
class Kontak extends Model {

    #[Column('id_kontak', key: true)]
    public int $idKontak;

    #[Column('nm_kontak')]
    public ?string $nmKontak;

    #[Column('id_departemen')]
    public ?int $idDepartemen;

    #[Column('id_unit')]
    public ?int $idUnit;

    #[Column('id_profesi')]
    public ?int $idProfesi;

    #[Column('created_at')]
    public ?DateTime $createdAt;

}
```

- `#[Table(nama, schema)]` wajib. `schema` adalah **nama koneksi** di
  `Manager`, bukan nama database.
- `#[Column(nama, key)]` memetakan properti ke kolom. Satu kolom harus
  bertanda `key: true`; kolom itu dipakai `find()`, `update()`, dan `delete()`.
- Nama properti bebas; kunci output `toArray()` memakai nama properti.
- `NotMapped` menandai properti yang tidak ikut kolom database.

## 2. Mengambil data

```php
$kontak = Kontak::find(5);
$semua = Kontak::all();
$terbaru = Kontak::all(fn($b) => $b->orderBy('id_kontak', 'desc')->limit(5));
$pertama = Kontak::first();
$jumlah = Kontak::count();
```

`find(5)` mengembalikan model atau `null`. `all()` mengembalikan
[`Collection`](../99-referensi/23-collection.md). Parameter `$query` opsional
dan menerima closure berisi `IQueryBuilder`, sama seperti `first()` dan
`count()`. Hasil `find(5)` pada data uji:

```json
{
    "idKontak": 5,
    "nmKontak": "John Doe",
    "idDepartemen": 1,
    "idUnit": 1,
    "idProfesi": 1,
    "createdAt": "2026-10-21 04:46:48"
}
```

Model `Kontak` pada aplikasi uji juga punya tiga properti `BelongsTo`. Karena
tidak dimuat dengan `with()`, ketiganya bernilai `null` dan tetap muncul di
`toArray()`. Lihat [relasi](02-relasi.md).

## 3. Query lanjutan

```php
$data = Kontak::query()
    ->orderBy('id_kontak')
    ->limit(2)
    ->all();

$jumlah = Kontak::where([['nm_kontak', 'LIKE', '%John%']])->count();
$halaman = Kontak::offset(1)->limit(2)->all(); // id 6 dan 7
```

`Model::query()` mengembalikan
[`ModelQuery`](../99-referensi/22-model-query.md); bentuk `where` sama dengan
[QueryBuilder](../99-referensi/16-query-builder.md).

`Model::first()` mengembalikan model pertama tanpa urutan eksplisit. Gunakan
`query()->orderBy(...)->first()` bila urutan penting.

## 4. Menulis data

### `create()`

```php
$kontak = Kontak::create([
    'nm_kontak' => 'Model Demo',
    'id_departemen' => 1,
    'id_unit' => 1,
    'id_profesi' => 1,
    'created_at' => new DateTime(),
]);

$id = $kontak->idKontak;
```

Kunci array memakai **nama kolom** (`id_kontak`, bukan `idKontak`). Semua kolom
non-key harus ada di array; kolom yang tidak dikirim akan memicu warning akses
key yang tidak ada.

### `update()` dan `delete()`

```php
$kontak->nmKontak = 'Model Demo Diubah';
$updated = $kontak->update();
$deleted = $kontak->delete();
```

Keduanya memakai kolom `key` sebagai kondisi `WHERE` dan mengembalikan `bool`.
Hasil pengujian (nilai id bergantung auto-increment):

```json
{
    "id": 10,
    "update": true,
    "delete": true,
    "sisa": true
}
```

`sisa` bernilai `true` berarti `find($id)` mengembalikan `null` setelah delete.

## 5. `toArray()` dan `toArrayDb()`

- `toArray()` menghasilkan bentuk API: kunci nama properti, nilai sudah
  diserialisasi (`DateTime` menjadi `Y-m-d H:i:s`).
- `toArrayDb()` menghasilkan bentuk database: kunci **nama kolom**, nilai sudah
  di-`set()` sesuai cast.

`toArrayDb()` pada `Kontak::find(5)`:

```json
{
    "id_kontak": 5,
    "nm_kontak": "John Doe",
    "id_departemen": 1,
    "id_unit": 1,
    "id_profesi": 1,
    "created_at": "2026-10-21 04:46:48"
}
```

`update()` memakai `toArrayDb()` sebagai data, jadi hanya properti bertanda
`Column` yang dikirim.

## 6. `NotMapped`

```php
#[Table('kontak', 'main')]
class KontakNotMapped extends Model {

    #[Column('id_kontak', key: true)]
    public int $idKontak;

    #[Column('nm_kontak')]
    public ?string $nmKontak;

    #[NotMapped]
    public ?string $catatan;

}
```

Hasil:

```json
{
    "toArray": {
        "idKontak": 5,
        "nmKontak": "John Doe",
        "catatan": "hanya di memori"
    },
    "toArrayDb": {
        "id_kontak": 5,
        "nm_kontak": "John Doe"
    }
}
```

`NotMapped` tidak ikut `toArrayDb()`, `create()`, maupun `update()`, tetapi
tetap diserialisasi oleh `toArray()` bila propertinya diisi.

## 7. Kolom terhitung

`Column` menerima `expression` untuk memakai fungsi SQL saat `SELECT`, tanpa
mengubah kolom fisik untuk tulis.

### String resolver

String menunjuk ke static method public pada model yang sama. Method menerima
context dan mengembalikan node `SelectNode`.

```php
use Selvi\Database\Builder\DML\Clauses\SelectNode;

#[Column('nm_kontak', expression: 'namaKontak')]
public ?string $nmKontak;

public static function namaKontak(array $context): array {
    return SelectNode::function('COALESCE', [
        SelectNode::column($context['column'], $context['source']),
        SelectNode::literal('Tanpa Nama'),
    ]);
}
```

Context berisi `alias`, `column`, `source` (tabel model), `prefix`, dan `model`.
Karena `source` berasal dari context, resolver tetap benar pada relasi
bertingkat. Node kolom tanpa `table` otomatis memakai `source`. Detail di
[`Column`](../99-referensi/25-column.md#expression).

### Array node

```php
#[Column('nm_kontak', expression: [
    'type' => 'function',
    'name' => 'COALESCE',
    'arguments' => [
        ['type' => 'column', 'column' => 'nm_kontak'],
        ['type' => 'literal', 'value' => 'Tanpa Nama'],
    ],
])]
public ?string $nmKontak;
```

Hasil `find(5)` dan SQL:

```json
{
    "idKontak": 5,
    "nmKontak": "John Doe"
}
```

```sql
SELECT kontak.id_kontak AS id_kontak,
       COALESCE(kontak.nm_kontak, 'Tanpa Nama') AS nm_kontak
FROM kontak
```

`toArrayDb()` tetap memakai kolom fisik:

```json
{
    "id_kontak": 5,
    "nm_kontak": "John Doe"
}
```

Aturan:

- Alias expression otomatis `{nama_kolom}` pada query utama dan
  `{prefix}_{nama_kolom}` pada relasi bertingkat; alias node diabaikan.
- Node kolom pada bentuk array otomatis dikualifikasi dengan tabel model.
- SQL mentah hanya lewat node array `['type' => 'raw', 'sql' => ...]`, tidak
  lewat string.
- Resolver wajib `public static` dan mengembalikan node dengan key `type`.
  Resolver yang tidak ada atau hasilnya bukan node ditolak dengan
  `InvalidArgumentException`.
- `cast`/`serialize` tetap berlaku pada hasil expression.
- Expression pada `key: true` ditolak.
- PHP tidak mengizinkan pemanggilan method di argumen attribute, jadi
  `SelectNode::function()` **tidak bisa** ditulis di `#[Column]`; gunakan string
  resolver atau array literal. Factory `SelectNode` tetap dipakai di kode
  builder dan di dalam resolver.

## Referensi API

- [`Model`](../99-referensi/21-model.md)
- [`ModelQuery`](../99-referensi/22-model-query.md)
- [`Collection`](../99-referensi/23-collection.md)
- [`Table`](../99-referensi/24-table.md), [`Column`](../99-referensi/25-column.md),
  [`NotMapped`](../99-referensi/26-not-mapped.md), dan
  [`SelectNode`](../99-referensi/30-select-node.md).

## Kesalahan umum

| Gejala | Penyebab | Perbaikan |
|---|---|---|
| `Kelas ... belum punya atribute Table` | `#[Table]` tidak ada | tambahkan attribute |
| `Call to a member function name() on null`/key kosong | tidak ada `Column(key: true)` | tandai satu kolom sebagai key |
| `Undefined array key "..."` saat `create()` | ada kolom non-key yang tidak dikirim | kirim semua kolom non-key |
| `find()` selalu `null` | nilai key/tipe tidak cocok | periksa nilai key dan kolom |
| `update()`/`delete()` tidak berefek | properti key belum terisi | ambil model lewat `find()` lebih dulu |
| `Resolver '...' tidak ditemukan` | nama di `expression` bukan method model | samakan dengan nama method statis |
| `Resolver '...' harus static dan public` | method resolver bukan `public static` | ubah deklarasi method |
| `Resolver '...' harus mengembalikan node` | method tidak mengembalikan array node | kembalikan hasil factory `SelectNode` |
| Relasi `null` | belum memakai `with()` | lihat [relasi](02-relasi.md) |

## Sumber

- [src/Model.php](../../src/Model.php) — metadata, query statis, `create()`, `update()`, `delete()`,
  `toArrayDb()`
- [src/Data/DataObject.php](../../src/Data/DataObject.php) — `of()` dan `toArray()`
- [src/Data/DataRow.php](../../src/Data/DataRow.php) dan [src/Data/DataProperty.php](../../src/Data/DataProperty.php) — assignment
  properti
- [src/Database/Builder/DML/ModelQuery.php](../../src/Database/Builder/DML/ModelQuery.php)
- [src/Model/Attributes/Table.php](../../src/Model/Attributes/Table.php), `Column.php`, `NotMapped.php`
- [tests/03.01-model/index.php](../../tests/03.01-model/index.php) — aplikasi uji halaman ini

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 1 November 2026 lewat `http://localhost:8089/03.01-model/`
(MariaDB 10.7.8, PHP 8.4.14):

| Endpoint | Hasil |
|---|---|
| `GET /03.01-model/` | `200`, dua model terurut |
| `GET /03.01-model/find` | `200`, satu model; relasi `null` |
| `GET /03.01-model/first-count` | `200`, id `5`, count `5` |
| `GET /03.01-model/where` | `200`, count `5` |
| `GET /03.01-model/offset` | `200`, id `[6,7]` |
| `GET /03.01-model/to-array-db` | `200`, kunci nama kolom |
| `GET /03.01-model/notmapped` | `200`, `catatan` ada di `toArray`, tidak di `toArrayDb` |
| `GET /03.01-model/create-update-delete` | `200`, id baru, update/delete `true` |
| `GET /03.01-model/ekspresi` | `200`, resolver `COALESCE`, SQL sesuai |
| `GET /03.01-model/ekspresi-array` | `200`, `COALESCE` array, kualifikasi otomatis |
| `GET /03.01-model/ekspresi-raw-model` | `200`, node `raw` dipakai apa adanya |
| `GET /03.01-model/ekspresi-resolver-missing` | `400`, resolver tidak ditemukan |
| `GET /03.01-model/ekspresi-resolver-nonstatic` | `400`, resolver tidak static |
| `GET /03.01-model/ekspresi-resolver-tipe` | `400`, tipe node tidak dikenali |
| `GET /03.01-model/ekspresi-resolver-invalid` | `400`, hasil resolver bukan node |
| `GET /03.01-model/ekspresi-count` | `200`, `count` `5` |
| `GET /03.01-model/ekspresi-toarraydb` | `200`, tetap memakai kolom fisik |
| `GET /03.01-model/ekspresi-key` | `400`, expression pada key ditolak |

Belum diuji: `Model::limit()` tanpa `offset`, model dengan nama koneksi default,
dan `create()` dengan kolom yang tidak lengkap.

Pada 5 November 2026, `Model::all($query)` diuji ulang lewat CLI (`php -r`) pada
data uji `kontak`: `Kontak::all()` mengembalikan 5 model,
`Kontak::all(fn($b) => $b->limit(2))` mengembalikan 2 model, dan
`Kontak::all(fn($b) => $b->orderBy('kontak.id_kontak', 'desc')->limit(1))`
mengembalikan id `9`.
