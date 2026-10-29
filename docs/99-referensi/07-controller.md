---
id: ref-controller
title: Selvi\Controller
type: referensi
class: Selvi\Controller
tags: [controller, base-class, opsional]
prerequisites: [controller]
verified: 2026-10-29
applies_to: Selvi 3.x
---

# Referensi: `Selvi\Controller`

Base class opsional untuk controller. Isinya hanya pewarisan dari
`Selvi\Base`, dan `Base` juga kosong. Tidak ada method, properti, atau
konstruktor yang dideklarasikan, sehingga tidak ada perilaku tambahan.

Controller tidak wajib mewarisi kelas ini. Pemakaian praktisnya ada di
[controller](../01-memulai/04-controller.md).

## Pewarisan

```php
namespace Selvi;

class Controller extends Base { }
```

```php
namespace Selvi;

class Base { }
```

## Method statis

Tidak ada.

## Method instance

Tidak ada.

## Helper

Tidak ada helper khusus untuk controller. Route memakai format
`Namespace\Kelas@method`; instance controller dibuat oleh framework, lalu
method dipanggil sebagai bagian dari alur
[`Framework::run()`](01-framework.md).

## Sumber

- `src/Controller.php` — deklarasi class.
- `src/Base.php` — parent class kosong.
- `tests/01.04-controller/Controllers/HaloController.php` — controller uji yang
  mewarisi `Selvi\Controller`.

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 29 Oktober 2026 (PHP 8.4.14) lewat
`http://localhost:8089/01.04-controller/`. `HaloController` mewarisi
`Selvi\Controller`, dan seluruh endpoint controller tetap berjalan normal.
Tidak ada perilaku tambahan yang perlu diuji karena kelas ini kosong.
