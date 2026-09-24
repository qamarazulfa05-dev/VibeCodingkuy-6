<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            // UNIQUE: satu buku hanya boleh punya satu peminjaman aktif (RB-04),
            // dijamin oleh database walaupun ada dua request yang masuk bersamaan.
            $table->foreignId('book_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->dateTime('tanggal_pinjam');
            $table->dateTime('tanggal_kembali'); // tanggal_pinjam + 7 hari (RB-05)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
