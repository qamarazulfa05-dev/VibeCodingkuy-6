<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Book extends Model
{
    protected $fillable = ['judul', 'penulis'];

    // Satu buku hanya boleh punya satu peminjaman aktif (dijaga juga oleh UNIQUE di database).
    public function loan(): HasOne
    {
        return $this->hasOne(Loan::class);
    }
}
