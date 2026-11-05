<?php

declare(strict_types=1);

namespace App\Models;

use DateTime;
use Selvi\Model;
use Selvi\Model\Attributes\Column;
use Selvi\Model\Attributes\Table;

#[Table('produk', schema: 'main')]
class Produk extends Model {

    #[Column('id_produk', key: true)]
    public int $idProduk;

    #[Column('nama')]
    public string $nama;

    #[Column('harga')]
    public int $harga;

    #[Column('stok')]
    public int $stok;

    #[Column('created_at')]
    public DateTime $createdAt;

    #[Column('updated_at')]
    public DateTime $updatedAt;

}
