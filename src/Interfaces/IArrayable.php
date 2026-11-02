<?php

namespace Selvi\Interfaces;

/**
 * Kontrak untuk objek yang bisa dikonversi menjadi array representasi publiknya.
 *
 * Dipakai Collection::toArray() untuk mengonversi item secara rekursif: objek
 * yang mengimplementasikan kontrak ini menentukan sendiri bentuk array-nya,
 * sehingga Collection tidak perlu tahu tipe konkret di dalamnya.
 */
interface IArrayable {

    /**
     * Representasi array dari objek ini.
     */
    public function toArray(): array;

}
