<?php

declare(strict_types=1);

namespace Selvi\Data;

/**
 * Sumber data mentah dan prefix jalur untuk hydration DTO/Model.
 *
 * Dipakai oleh IDataProperty::assign().
 */
final class DataRow {

    public function __construct(
        private object $item,
        private string $prefix = ""
    ) { }

    public function item(): object {
        return $this->item;
    }

    public function prefix(): string {
        return $this->prefix;
    }

    public function key(string $name): string {
        return $this->prefix === "" ? $name : "{$this->prefix}_{$name}";
    }

    public function has(string $name): bool {
        return property_exists($this->item, $this->key($name));
    }

    public function get(string $name): mixed {
        return $this->item->{$this->key($name)};
    }

    public function append(string $suffix): string {
        return "{$this->prefix}{$suffix}";
    }

    public function getAppended(string $suffix): mixed {
        return $this->item->{$this->append($suffix)};
    }

    public function child(string $name): self {
        return new self($this->item, $this->append("{$name}_"));
    }

    public function hasAny(string $prefix): bool {
        if($prefix === "") return false;

        foreach(get_object_vars($this->item) as $key => $value) {
            if($value !== null && str_starts_with($key, $prefix)) return true;
        }

        return false;
    }
}
