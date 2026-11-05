<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Produk;
use DateTime;
use Selvi\Exception;
use Selvi\Input\Request;
use stdClass;

class ProdukController {

    public function __construct(private Request $request) { }

    public function result() {
        $query = Produk::query();
        $q = trim((string) ($this->request->get('q') ?? ''));
        if($q !== '') $query->where([['nama', 'LIKE', '%'.$q.'%']]);

        $count = (clone $query)->count();

        $limit = (int) ($this->request->get('limit') ?? 30);
        $limit = min(100, max(1, $limit));
        $offset = max(0, (int) ($this->request->get('offset') ?? 0));
        $rows = $query->orderBy('id_produk', 'DESC')
            ->limit($limit)
            ->offset($offset)
            ->all();

        return jsonResponse([
            'data' => $rows->toArray(),
            'count' => $count
        ]);
    }

    public function row(string $id) {
        return jsonResponse($this->find($id)->toArray());
    }

    public function insert() {
        $input = $this->validate($this->jsonBody());
        $now = new DateTime();

        $produk = Produk::create([
            'nama' => $input['nama'],
            'harga' => $input['harga'],
            'stok' => $input['stok'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return jsonResponse($produk->toArray(), 201);
    }

    public function update(string $id) {
        $produk = $this->find($id);
        $input = $this->validate($this->jsonBody(), partial: true);

        foreach($input as $name => $value) {
            $produk->{$name} = $value;
        }
        $produk->updatedAt = new DateTime();
        $produk->update();

        return jsonResponse($produk->toArray());
    }

    public function delete(string $id) {
        $this->find($id)->delete();
        return jsonResponse(null, 204);
    }

    private function find(string $id): Produk {
        if(!ctype_digit($id)) {
            throw new Exception('ID produk harus berupa angka.', 'produk/invalid-id', 400);
        }

        $produk = Produk::find((int) $id);
        if($produk === null) {
            throw new Exception('Produk tidak ditemukan.', 'produk/not-found', 404);
        }

        return $produk;
    }

    /** @return array<string, mixed> */
    private function jsonBody(): array {
        $raw = $this->request->raw();
        if($raw === null || trim($raw) === '') {
            throw new Exception('Body JSON wajib diisi.', 'produk/invalid-json', 400);
        }

        $decoded = json_decode($raw);
        $data = json_decode($raw, true);
        if(!($decoded instanceof stdClass) || !is_array($data)) {
            throw new Exception('Body harus berupa objek JSON yang valid.', 'produk/invalid-json', 400);
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return array{nama?: string, harga?: int, stok?: int}
     */
    private function validate(array $data, bool $partial = false): array {
        $errors = [];
        $hasil = [];

        if(!$partial || array_key_exists('nama', $data)) {
            $nama = is_string($data['nama'] ?? null) ? trim($data['nama']) : '';
            if($nama === '') {
                $errors['nama'] = 'Nama wajib diisi.';
            } elseif(strlen($nama) > 100) {
                $errors['nama'] = 'Nama maksimal 100 karakter.';
            } else {
                $hasil['nama'] = $nama;
            }
        }

        if(!$partial || array_key_exists('harga', $data)) {
            $harga = $data['harga'] ?? null;
            if(!is_int($harga) || $harga < 0) {
                $errors['harga'] = 'Harga wajib berupa bilangan bulat >= 0.';
            } else {
                $hasil['harga'] = $harga;
            }
        }

        if(array_key_exists('stok', $data)) {
            $stok = $data['stok'];
            if(!is_int($stok) || $stok < 0) {
                $errors['stok'] = 'Stok wajib berupa bilangan bulat >= 0.';
            } else {
                $hasil['stok'] = $stok;
            }
        } elseif(!$partial) {
            $hasil['stok'] = 0;
        }

        if($partial && $hasil === [] && $errors === []) {
            throw new Exception('Tidak ada field yang bisa diperbarui.', 'produk/invalid', 422);
        }

        if($errors !== []) {
            throw (new Exception('Data produk tidak valid.', 'produk/invalid', 422))
                ->with('errors', $errors);
        }

        return $hasil;
    }

}
