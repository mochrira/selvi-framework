---
id: ref-converter
title: Selvi\Database\Casting\Converter
type: referensi
class: Selvi\Database\Casting\Converter
tags: [model, cast, serialize, converter, icast, enum, datetime]
prerequisites: [model-konversi-value]
verified: 2026-11-01
applies_to: Selvi 3.x
---

# Referensi: `Selvi\Database\Casting\Converter`

Pusat konversi nilai database ↔ properti ↔ output.

Aturan pemilihan cast, berurutan:

1. cast eksplisit (`Column(cast: ...)`),
2. cast yang didaftarkan `register()`,
3. cast bawaan tipe skalar (`int`, `float`, `bool`, `string`),
4. introspeksi `BackedEnum` dan `DateTimeInterface`,
5. tidak ada cast → nilai dilewatkan apa adanya.

Hasil resolusi di-cache per tipe. `register()` membersihkan cache tipe itu.

Pemakaian ada di [konversi value](../03-model/03-konversi-value.md).

## Method statis

### `Converter::instance()`

**Kembalian:** instance Converter (satu per kelas).

## Method instance

### `Converter::register($type, $cast)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$type` | `string` | wajib | nama tipe properti, mis. `'uang'` |
| `$cast` | `ICast` | wajib | implementasi cast |

**Kembalian:** instance Converter baru/ini. Cast terdaftar menang atas bawaan.

### `Converter::get($type, $value, $cast)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$type` | `?string` | wajib | nama tipe properti |
| `$value` | `mixed` | wajib | nilai sumber |
| `$cast` | `?ICast` | `null` | cast eksplisit |

**Kembalian:** `mixed` — nilai properti. `null` dilewatkan apa adanya.

### `Converter::set($type, $value, $cast)`

Sama seperti `get()`, arah properti → database.

**Kembalian:** `mixed` — nilai siap simpan (bukan literal SQL).

### `Converter::serialize($type, $value, $cast, $serialize)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$type` | `?string` | wajib | nama tipe properti |
| `$value` | `mixed` | wajib | nilai properti |
| `$cast` | `?ICast` | `null` | cast eksplisit |
| `$serialize` | `?ISerializesCast` | `null` | serializer eksplisit |

**Kembalian:** `mixed`. Urutan: serializer eksplisit → cast yang juga
`ISerializesCast` → `IArrayable::toArray()` → nilai apa adanya.

## Cast bawaan

| File | Tipe |
|---|---|
| `IntCast` | `int` |
| `FloatCast` | `float` |
| `BoolCast` | `bool` |
| `StringCast` | `string` |
| `EnumCast` | `BackedEnum` |
| `DateTimeCast` | `DateTimeInterface` |
| `ArrayCast` | tidak otomatis; dipakai eksplisit untuk kolom JSON |

## Sumber

- [src/Database/Casting/Converter.php](../../src/Database/Casting/Converter.php)
- `src/Database/Casting/*Cast.php`
- [src/Database/Interfaces/ICast.php](../../src/Database/Interfaces/ICast.php), `ISerializesCast.php`
- [tests/03.03-konversi-value/index.php](../../tests/03.03-konversi-value/index.php) — aplikasi uji

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 1 November 2026 lewat `tests/03.03-konversi-value`:

| Kasus | Hasil |
|---|---|
| `get('int', '12')` | `12` |
| `get('bool', '0')` | `false` |
| `get('float', '1.5')` | `1.5` |
| `get('string', 10)` | `'10'` |
| `set('int', '12')` | `'12'` |
| `serialize('int', 12)` | `12` |
| `ArrayCast` JSON valid/rusak | array / `null` |
| `DateTimeCast` + `DATE_ATOM` | format simpan / ATOM |
| `EnumCast` | enum / nilai backing |
| `register('uang', UangCast)` | `1500` / `Rp 1.500` |

Belum diuji: `register()` menimpa cast bawaan, `FloatCast`/`BoolCast`/
`StringCast` dengan nilai `null`, dan cast bersarang (`IArrayable` di dalam
`ArrayCast`).
