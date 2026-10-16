<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Borrowing extends Model
{
    public const STATUS_DIPINJAM = 'dipinjam';
    public const STATUS_DIKEMBALIKAN = 'dikembalikan';

    protected $fillable = [
        'book_id',
        'student_id',
        'tanggal_pinjam',
        'tanggal_jatuh_tempo',
        'tanggal_dikembalikan',
        'status',
        'active_book_id',
    ];

    protected $casts = [
        'tanggal_pinjam' => 'datetime',
        'tanggal_jatuh_tempo' => 'datetime',
        'tanggal_dikembalikan' => 'datetime',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** Peminjaman yang belum dikembalikan. */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DIPINJAM);
    }

    /**
     * Bentuk JSON yang dikirim ke client.
     * Relasi `book` dan `student` harus sudah dimuat.
     */
    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'idBuku' => $this->book_id,
            'judulBuku' => $this->book->judul,
            'penulisBuku' => $this->book->penulis,
            'nimMahasiswa' => $this->student->nim,
            'status' => $this->status,
            'tanggalPinjam' => $this->tanggal_pinjam->copy()->utc()->toIso8601ZuluString(),
            'tanggalJatuhTempo' => $this->tanggal_jatuh_tempo->copy()->utc()->toIso8601ZuluString(),
            'tanggalDikembalikan' => $this->tanggal_dikembalikan?->copy()->utc()->toIso8601ZuluString(),
        ];
    }
}
