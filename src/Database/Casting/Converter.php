<?php

declare(strict_types=1);

namespace Selvi\Database\Casting;

use BackedEnum;
use DateTimeInterface;
use Selvi\Contracts\Arrayable;

/**
 * Pusat konversi value: DB <-> model dan model -> output (toArray/JSON).
 *
 * Converter hanya tahu tipe property dan cast yang menanganinya. Cast dipilih
 * dengan urutan:
 *
 *   1. cast yang diminta eksplisit (mis. dari attribute Column),
 *   2. cast yang didaftarkan lewat register(),
 *   3. cast bawaan untuk tipe skalar: int, float, bool, string,
 *   4. introspeksi kelas: BackedEnum dan DateTimeInterface,
 *   5. tidak ada cast yang cocok -> value dilewatkan apa adanya.
 *
 * Hasil resolusi di-cache per tipe; register() membersihkan cache tipe itu
 * sehingga override yang didaftarkan belakangan tetap berlaku.
 */
class Converter {

    /**
     * @var array<class-string<Converter>, Converter>
     */
    private static array $instances = [];

    /**
     * Cast yang didaftarkan aplikasi, menang atas cast bawaan.
     *
     * @var array<string, CastInterface>
     */
    private array $registered = [];

    /**
     * Hasil resolusi per tipe (nama tipe atau nama kelas).
     *
     * @var array<string, CastInterface|null>
     */
    private array $resolved = [];

    /**
     * @var array<string, CastInterface>|null
     */
    private ?array $defaults = null;

    /**
     * Converter bawaan aplikasi: satu instance per kelas, dibuat saat dipakai
     * pertama kali.
     */
    public static function instance(): static {
        return self::$instances[static::class] ??= new static();
    }

    /**
     * Mendaftarkan cast untuk sebuah tipe property.
     *
     * Karena menang atas cast bawaan, pemanggilan ini juga bisa dipakai untuk
     * menimpa perilaku default.
     *
     *     Converter::instance()->register(Money::class, new MoneyCast());
     *
     * @param string $type Nama tipe property, mis. 'int' atau Money::class.
     */
    public function register(string $type, CastInterface $cast): static {
        $this->registered[$type] = $cast;
        unset($this->resolved[$type]);

        return $this;
    }

    /**
     * DB -> model.
     */
    public function get(?string $type, mixed $value, ?CastInterface $cast = null): mixed {
        $cast ??= $this->resolve($type);

        return $cast === null ? $value : $cast->get($value);
    }

    /**
     * model -> DB.
     */
    public function set(?string $type, mixed $value, ?CastInterface $cast = null): mixed {
        $cast ??= $this->resolve($type);

        return $cast === null ? $value : $cast->set($value);
    }

    /**
     * model -> output (toArray/JSON).
     *
     * Serializer dipilih dari $serialize, lalu cast yang bersangkutan bila ia
     * mengimplementasikan SerializesCastInterface. Bila tidak ada, value
     * dikembalikan apa adanya - untuk tipe skalar itu memang bentuk yang benar.
     */
    public function serialize(?string $type, mixed $value, ?CastInterface $cast = null, ?SerializesCastInterface $serialize = null): mixed {
        if($serialize === null) {
            $resolved = $cast ?? $this->resolve($type);

            if($resolved instanceof SerializesCastInterface) $serialize = $resolved;
        }

        if($serialize !== null) return $serialize->serialize($value);

        return $value instanceof Arrayable ? $value->toArray() : $value;
    }

    /**
     * Mencari cast untuk sebuah tipe property, atau null bila tidak ada.
     */
    private function resolve(?string $type): ?CastInterface {
        if($type === null) return null;
        if(array_key_exists($type, $this->resolved)) return $this->resolved[$type];

        $cast = $this->registered[$type] ?? $this->defaults()[$type] ?? null;

        if($cast === null && is_a($type, BackedEnum::class, true)) {
            $cast = new EnumCast($type);
        }

        if($cast === null && is_a($type, DateTimeInterface::class, true)) {
            $cast = new DateTimeCast($type);
        }

        return $this->resolved[$type] = $cast;
    }

    /**
     * @return array<string, CastInterface>
     */
    private function defaults(): array {
        return $this->defaults ??= [
            'int'    => new IntCast(),
            'float'  => new FloatCast(),
            'bool'   => new BoolCast(),
            'string' => new StringCast(),
        ];
    }

}
