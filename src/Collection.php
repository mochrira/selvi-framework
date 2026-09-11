<?php

namespace Selvi;

/**
 * Type-safe list class — setara dengan List<T> di C#.
 *
 * @template T
 */
class Collection
{
    /** @var array<int, T> */
    private array $items = [];

    /**
     * @param array<int, T> $items
     */
    public function __construct(array $items = [])
    {
        $this->items = array_values($items);
    }

    /**
     * Menambahkan item ke akhir list.
     *
     * @param T $item
     * @return void
     */
    public function add(mixed $item): void
    {
        $this->items[] = $item;
    }

    /**
     * Menghapus item pada index tertentu.
     *
     * @param int $index
     * @return void
     */
    public function remove(int $index): void
    {
        if (isset($this->items[$index])) {
            array_splice($this->items, $index, 1);
        }
    }

    /**
     * Mengambil item pada index tertentu.
     *
     * @param int $index
     * @return T|null
     */
    public function get(int $index): mixed
    {
        return $this->items[$index] ?? null;
    }

    /**
     * Mengubah item pada index tertentu.
     *
     * @param int $index
     * @param T $item
     * @return void
     */
    public function set(int $index, mixed $item): void
    {
        $this->items[$index] = $item;
    }

    /**
     * Menjalankan callback untuk setiap item.
     *
     * @param callable(T): void $callback
     * @return void
     */
    public function forEach(callable $callback): void
    {
        foreach ($this->items as $item) {
            $callback($item);
        }
    }

    /**
     * Mentransformasi setiap item dan mengembalikan list baru.
     *
     * @template U
     * @param callable(T): U $callback
     * @return Collection<U>
     */
    public function map(callable $callback): Collection
    {
        return new Collection(array_map($callback, $this->items));
    }

    /**
     * Menyaring item berdasarkan kriteria.
     *
     * @param callable(T): bool $callback
     * @return Collection<T>
     */
    public function filter(callable $callback): Collection
    {
        return new Collection(array_values(array_filter($this->items, $callback)));
    }

    /**
     * Mereduksi list menjadi sebuah nilai tunggal.
     *
     * @template U
     * @param callable(U|T, T): U $callback
     * @param U $initial
     * @return U
     */
    public function reduce(callable $callback, mixed $initial): mixed
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * Jumlah item dalam list.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Apakah list kosong.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Mengambil seluruh item sebagai array.
     *
     * @return array<int, T>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * Mengosongkan list.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->items = [];
    }

    /**
     * Static factory — shortcut untuk new Collection(...).
     *
     * @template U
     * @param U ...$items
     * @return Collection<U>
     */
    public static function of(array $items): Collection
    {
        return new Collection($items);
    }

    /**
     * Static factory dari array.
     *
     * @template U
     * @param array<int, U> $items
     * @return Collection<U>
     */
    public static function fromMap(callable $map, array $items): Collection
    {
        return new Collection(array_map($map, $items));
    }
}