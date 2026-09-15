<?php 

namespace Selvi\Database\Attributes;

use Selvi\Database\Contracts\CastInterface;
use Selvi\Database\Contracts\SerializesCastInterface;

/**
 * Pemetaan satu property model ke satu kolom database.
 *
 * cast dan serialize bersifat opsional; keduanya adalah OVERRIDE. Tanpa keduanya,
 * cast ditentukan dari tipe property (lihat Converter): int/float/bool/string,
 * BackedEnum, dan DateTimeInterface sudah tertangani otomatis.
 *
 * Isinya instance, karena argumen attribute menerima `new`:
 *
 *     #[Column('createdAt', cast: new DateTimeCast())]
 *     #[Column('status', cast: new EnumCast(Status::class))]
 *     #[Column('options', cast: new JsonCast(), serialize: new JsonSerialize())]
 *
 * Aturan resolusi: cast() dulu, lalu serialize() bila diisi. Bila serialize()
 * kosong sedangkan cast-nya mengimplementasikan SerializesCastInterface, cast
 * itu yang dipakai untuk output.
 */
#[\Attribute]
class Column {

    public function __construct(
        public string $name,
        public bool $key = false,
        public ?CastInterface $cast = null,
        public ?SerializesCastInterface $serialize = null
    ) { }

}