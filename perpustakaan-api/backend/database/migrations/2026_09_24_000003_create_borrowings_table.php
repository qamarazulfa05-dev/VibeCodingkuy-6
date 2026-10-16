<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrowings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();

            $table->dateTime('tanggal_pinjam');
            $table->dateTime('tanggal_jatuh_tempo');          // tanggal_pinjam + 7 hari (BR-03)
            $table->dateTime('tanggal_dikembalikan')->nullable(); // NULL = belum dikembalikan

            // Status pengembalian: 'dipinjam' (aktif) atau 'dikembalikan'.
            $table->string('status', 20)->default('dipinjam');

            // Penjaga di level database: satu buku hanya boleh punya SATU peminjaman aktif (BR-02).
            // Kolom ini berisi book_id selama dipinjam dan di-NULL-kan saat dikembalikan.
            // UNIQUE mengizinkan banyak NULL, sehingga riwayat peminjaman lama tetap tersimpan.
            $table->unsignedBigInteger('active_book_id')->nullable()->unique();

            $table->timestamps();

            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrowings');
    }
};
