<?php

namespace Selvi\Database\Builder\DDL;

use InvalidArgumentException;
use Selvi\Database\Builder\DDL\Clauses\ColumnClause;
use Selvi\Database\Contracts\BlueprintInterface;

/**
 * Collector definisi tabel untuk `create()`.
 *
 * Menerima closure dari pemanggil, dan setiap method tipe di bawah menghasilkan
 * ColumnClause yang sudah dicatat di sini — lalu dikembalikan supaya modifier
 * kolom bisa dirantai:
 *
 *     $table->integer('idKontak')->key()->autoIncrement();
 *     $table->string('nmKontak', 100)->nullable();
 *
 * Objek ini sengaja hanya menyimpan struktur; rendering SQL adalah tugas Grammar
 * dan eksekusinya tugas SchemaBuilder.
 */
class Blueprint implements BlueprintInterface {

    /**
     * @var ColumnClause[]
     */
    private array $columns = [];

    public function __construct(
        private readonly string $table,
        private bool $ifNotExists = true
    ) { }

    public function integer(string $name, bool $nullable = false): ColumnClause {
        return $this->add($name, 'integer', ['nullable' => $nullable]);
    }

    public function bigInteger(string $name, bool $nullable = false): ColumnClause {
        return $this->add($name, 'bigInteger', ['nullable' => $nullable]);
    }

    public function string(string $name, int $length = 255, bool $nullable = false): ColumnClause {
        return $this->add($name, 'string', ['length' => $length, 'nullable' => $nullable]);
    }

    public function text(string $name, bool $nullable = false): ColumnClause {
        return $this->add($name, 'text', ['nullable' => $nullable]);
    }

    public function boolean(string $name, bool $nullable = false): ColumnClause {
        return $this->add($name, 'boolean', ['nullable' => $nullable]);
    }

    public function decimal(string $name, int $precision = 10, int $scale = 0, bool $nullable = false): ColumnClause {
        return $this->add($name, 'decimal', [
            'precision' => $precision,
            'scale' => $scale,
            'nullable' => $nullable,
        ]);
    }

    public function float(string $name, bool $nullable = false): ColumnClause {
        return $this->add($name, 'float', ['nullable' => $nullable]);
    }

    public function date(string $name, bool $nullable = false): ColumnClause {
        return $this->add($name, 'date', ['nullable' => $nullable]);
    }

    public function datetime(string $name, bool $nullable = false): ColumnClause {
        return $this->add($name, 'datetime', ['nullable' => $nullable]);
    }

    /**
     * Mengatur pemakaian "IF NOT EXISTS". Default true.
     */
    public function ifNotExists(bool $flag = true): static {
        $this->ifNotExists = $flag;
        return $this;
    }

    public function getTable(): string {
        return $this->table;
    }

    public function hasIfNotExists(): bool {
        return $this->ifNotExists;
    }

    public function getColumns(): array {
        return array_map(fn(ColumnClause $column) => $column->toArray(), $this->columns);
    }

    /**
     * Mencatat satu kolom, lalu mengembalikan ColumnClause-nya supaya modifier
     * bisa dirantai. Satu-satunya tempat yang menyentuh $columns.
     *
     * @param array<string, mixed> $options Nilai awal length/precision/scale/nullable.
     * @throws InvalidArgumentException Bila nama kolom kosong atau duplikat.
     */
    private function add(string $name, string $type, array $options = []): ColumnClause {
        $column = ColumnClause::make($name, $type, $options);

        foreach($this->columns as $existing) {
            if($existing->name() === $column->name()) {
                throw new InvalidArgumentException("Kolom '{$name}' didefinisikan lebih dari sekali pada tabel {$this->table}.");
            }
        }

        $this->columns[] = $column;

        return $column;
    }

}
