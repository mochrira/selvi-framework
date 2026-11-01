---
id: ref-collection
title: Selvi\Collection
type: referensi
class: Selvi\Collection
tags: [collection, list, map, filter, reduce, model]
prerequisites: [model]
verified: 2026-11-01
applies_to: Selvi 3.x
---

# Referensi: `Selvi\Collection`

List bertipe untuk hasil `Model::all()`/`find()` dan pemakaian umum. Kelas ini
mengimplementasikan `Selvi\Interfaces\IArrayable`.

Aturan yang berlaku:

- Index selalu dirapikan dengan `array_values()`.
- `toArray()` mengonversi item yang `IArrayable` (mis. model) lewat
  `toArray()` milik item.
- `map()` dan `filter()` mengembalikan `Collection` baru.

## Method instance

### `Collection::add($item)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$item` | `mixed` | wajib | item baru |

**Kembalian:** `void`.

### `Collection::remove($index)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$index` | `int` | wajib | index yang dihapus |

**Kembalian:** `void`. Index yang tidak ada diabaikan.

### `Collection::get($index)`

**Kembalian:** `mixed|null`.

### `Collection::set($index, $item)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$index` | `int` | wajib | index |
| `$item` | `mixed` | wajib | nilai baru |

**Kembalian:** `void`.

### `Collection::forEach($callback)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$callback` | `callable(T): void` | wajib | callback per item |

**Kembalian:** `void`.

### `Collection::map($callback)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$callback` | `callable(T): U` | wajib | transformasi |

**Kembalian:** `Collection<U>`.

### `Collection::filter($callback)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$callback` | `callable(T): bool` | wajib | predikat |

**Kembalian:** `Collection<T>`.

### `Collection::reduce($callback, $initial)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$callback` | `callable(U|T, T): U` | wajib | reducer |
| `$initial` | `mixed` | wajib | nilai awal |

**Kembalian:** `mixed`.

### `Collection::count()`

**Kembalian:** `int`.

### `Collection::isEmpty()`

**Kembalian:** `bool`.

### `Collection::toArray()`

**Kembalian:** `array<int, mixed>` — item `IArrayable` dikonversi rekursif.

### `Collection::clear()`

**Kembalian:** `void`.

## Method statis

### `Collection::of($items)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$items` | `array` | wajib | daftar item |

**Kembalian:** `Collection`.

### `Collection::fromMap($map, $items)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$map` | `callable` | wajib | transformasi per item |
| `$items` | `array` | wajib | daftar sumber |

**Kembalian:** `Collection` hasil `array_map`.

## Sumber

- `src/Collection.php`
- `src/Interfaces/IArrayable.php`
- `tests/03.01-model/index.php` — pemakaian `map()` dan `all()`

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 1 November 2026:

| Kasus | Hasil |
|---|---|
| `Model::all()` menghasilkan `Collection` | `toArray()` berisi model |
| `map(fn($item) => $item->idKontak)->toArray()` | `[6,7]` |
| `Model::find(5)->toArray()` | model tunggal, bukan Collection |

Belum diuji: `add()`, `remove()`, `get()`, `set()`, `forEach()`, `filter()`,
`reduce()`, `count()`, `isEmpty()`, `clear()`, `of()`, dan `fromMap()` secara
langsung.
