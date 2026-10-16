<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Book extends Model
{
    protected $fillable = ['judul', 'penulis'];

    public function borrowings(): HasMany
    {
        return $this->hasMany(Borrowing::class);
    }

    // Peminjaman aktif (belum dikembalikan). Ada = buku berstatus "dipinjam".
    // Status ketersediaan sengaja DIHITUNG dari sini, bukan disimpan di kolom terpisah,
    // sehingga tidak mungkin tidak sinkron dengan data transaksi.
    public function activeBorrowing(): HasOne
    {
        return $this->hasOne(Borrowing::class)->where('status', Borrowing::STATUS_DIPINJAM);
    }

    /** Bentuk JSON buku. Panggil dengan withExists('activeBorrowing'). */
    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'judul' => $this->judul,
            'penulis' => $this->penulis,
            'status' => $this->active_borrowing_exists ? 'dipinjam' : 'tersedia',
        ];
    }
}
