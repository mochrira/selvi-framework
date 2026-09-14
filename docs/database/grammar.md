# Grammar & Struktur WHERE

Halaman ini untuk Anda yang menambah atau mengubah driver database.
Selvi memisahkan **penyusunan struktur** dari **perenderan SQL**, sehingga driver
yang berbeda mengonsumsi struktur yang sama dan hanya berbeda saat mencetak SQL.

## Pembagian Tanggung Jawab

| Lapisan | Tugas | Sifat |
|---|---|---|
| `QueryBuilder` | Menyusun tabel, kolom, dan kondisi | Netral driver |
| `WhereBuilder` | Mengumpulkan kondisi dari input `where()` / `orWhere()` | Netral driver |
| `WhereClause` | Memvalidasi dan menormalkan **satu** kondisi menjadi node | Netral driver |
| `Grammar` | Merender struktur menjadi SQL sesuai dialek | Spesifik driver |
| `Sanitizer` | Mengubah value PHP menjadi literal SQL | Spesifik driver |

Konsekuensinya: **Grammar hanya membaca**. `compileSelect()` tidak boleh mengubah
state builder, supaya memanggilnya berkali-kali selalu menghasilkan SQL yang sama.

## Struktur Kanonik (AST)

`WhereBuilder::toArray()` — dan juga `QueryBuilder::wheres()` — mengembalikan array
node. Ada empat tipe node:

```php
// Perbandingan
['type' => 'basic',  'boolean' => 'AND', 'column' => 'a', 'operator' => '=', 'value' => 1]

// Pengecekan null
['type' => 'null',   'boolean' => 'AND', 'column' => 'a', 'operator' => 'IS NOT NULL']

// Fragmen SQL mentah, tidak melalui sanitizer
['type' => 'raw',    'boolean' => 'OR',  'sql' => 'b IS NOT NULL']

// Grup bertingkat, dirender sebagai ( ... )
['type' => 'nested', 'boolean' => 'AND', 'wheres' => [ /* node anak, rekursif */ ]]
```

Catatan:

- `boolean` selalu terisi (`AND` / `OR`), termasuk node pertama. Grammar yang
  bertanggung jawab tidak menuliskan prefix pada node indeks `0`.
- Node `nested` menyimpan node anak pada key `wheres`, sehingga seluruh struktur
  bisa direkursi sebagai array biasa tanpa menyentuh objek builder.
- Array kosong berarti tidak ada kondisi WHERE.

## Aturan Normalisasi

Normalisasi yang berlaku untuk semua driver dikerjakan di `WhereClause`, supaya
setiap Grammar tidak perlu menuliskannya ulang:

| Input | Menjadi |
|---|---|
| `['a', 1]` (dua elemen) | `basic` dengan operator `=` |
| `['a', '!=', 1]` | operator `<>` (bentuk kanonik ANSI) |
| `['a', null]` atau `['a', 'IS', null]` | node `null` → `IS NULL` |
| `['a', '!=', null]` | node `null` → `IS NOT NULL` |
| operator huruf kecil | di-uppercase |

Operator lain seperti `LIKE`, `IN`, dan `BETWEEN` diteruskan apa adanya.

## Merender di Grammar

```php
protected function compileWhere(array $wheres): string
{
    if (empty($wheres)) {
        return '';
    }

    $parts = [];
    foreach ($wheres as $i => $w) {
        $clause = $this->compileWhereClause($w);
        $parts[] = ($i === 0) ? $clause : ($w['boolean'] . ' ' . $clause);
    }

    return implode(' ', $parts);
}

protected function compileWhereClause(array $w): string
{
    return match ($w['type']) {
        WhereClause::TYPE_RAW    => $w['sql'],
        WhereClause::TYPE_NULL   => $w['column'] . ' ' . $w['operator'],
        WhereClause::TYPE_BASIC  => $w['column'] . ' ' . $w['operator'] . ' ' . $this->sanitizer->sanitize($w['value']),
        WhereClause::TYPE_NESTED => '(' . $this->compileWhere($w['wheres']) . ')',
        default => throw new InvalidArgumentException('Tipe kondisi tidak dikenal: ' . $w['type']),
    };
}
```

Tipe node yang tidak dikenal sebaiknya **dilempar sebagai exception**, bukan
diabaikan diam-diam — supaya driver yang belum mendukung fitur baru gagal dengan
jelas alih-alih menghasilkan SQL yang salah.

## Yang Menjadi Tanggung Jawab Grammar

- **Identifier quoting.** Selvi belum melakukan quoting otomatis. Karena kolom kini
  hanya disentuh di Grammar, menambahkan `wrap(string $identifier)` adalah perubahan
  kecil yang terisolasi dan tidak menyentuh lapisan struktur.
- **Gaya placeholder.** Saat ini Grammar mengembalikan string SQL tanpa parameter
  binding, sehingga value harus dikonversi menjadi literal lewat `SanitizerInterface`.

## Menambah Tipe Node Baru

1. Tambahkan konstanta tipe di `WhereClause`.
2. Tambahkan factory dan cabang di `toArray()`.
3. Tangani tipe tersebut di setiap Grammar.

Karena `compileSelect()` menerima `QueryBuilderInterface`, Grammar tidak bisa
memutasi builder. Jika sebuah Grammar butuh data baru, tambahkan pada kontrak baca
tersebut — bukan dengan mengakses properti internal builder.
