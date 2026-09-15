<?php

declare(strict_types=1);

namespace Selvi\Database\Casting;

/**
 * Serialisasi value untuk toArray()/JSON.
 *
 * Padanan SerializesCastableAttributes di Laravel. Interface ini opsional: hanya
 * dipakai oleh cast yang bentuk PHP-nya tidak bisa langsung di-encode, mis.
 * objek DateTime dan enum.
 *
 * Cast yang tidak mengimplementasikannya (mis. cast tipe skalar) tidak perlu
 * serializer terpisah, karena value-nya sudah dalam bentuk yang diinginkan.
 */
interface SerializesCastInterface {

    /**
     * model -> output (toArray()/JSON).
     */
    public function serialize(mixed $value): mixed;

}
