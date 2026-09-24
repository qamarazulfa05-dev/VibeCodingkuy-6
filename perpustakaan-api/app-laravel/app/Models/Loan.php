<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Loan extends Model
{
    protected $fillable = ['book_id', 'student_id', 'tanggal_pinjam', 'tanggal_kembali'];

    protected $casts = [
        'tanggal_pinjam' => 'datetime',
        'tanggal_kembali' => 'datetime',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Bentuk JSON yang dikirim ke client. Nama field sengaja disamakan
     * dengan versi microservice sebelumnya supaya frontend lama tetap jalan.
     * Relasi `book` dan `student` harus sudah dimuat.
     */
    public function toApi(): array
    {
        return [
            'idBuku' => $this->book_id,
            'judulBuku' => $this->book->judul,
            'penulisBuku' => $this->book->penulis,
            'nimMahasiswa' => $this->student->nim,
            'tanggalPinjam' => $this->tanggal_pinjam->copy()->utc()->toIso8601ZuluString(),
            'tanggalKembali' => $this->tanggal_kembali->copy()->utc()->toIso8601ZuluString(),
        ];
    }
}
