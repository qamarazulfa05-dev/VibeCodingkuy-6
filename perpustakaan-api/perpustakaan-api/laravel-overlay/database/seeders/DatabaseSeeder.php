<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Student;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Akun uji. Password di-hash otomatis oleh cast pada model Student.
        $students = [
            ['nim' => '230001', 'nama' => 'Mahasiswa Satu', 'password' => 'password123'],
            ['nim' => '230002', 'nama' => 'Mahasiswa Dua', 'password' => 'password123'],
        ];

        foreach ($students as $s) {
            Student::updateOrCreate(['nim' => $s['nim']], $s);
        }

        $books = [
            ['judul' => 'Laskar Pelangi', 'penulis' => 'Andrea Hirata'],
            ['judul' => 'Bumi Manusia', 'penulis' => 'Pramoedya Ananta Toer'],
            ['judul' => 'Negeri 5 Menara', 'penulis' => 'Ahmad Fuadi'],
            ['judul' => 'Laut Bercerita', 'penulis' => 'Leila S. Chudori'],
            ['judul' => 'Filosofi Teras', 'penulis' => 'Henry Manampiring'],
            ['judul' => 'Atomic Habits', 'penulis' => 'James Clear'],
            ['judul' => 'Pulang', 'penulis' => 'Tere Liye'],
            ['judul' => 'The Psychology of Money', 'penulis' => 'Morgan Housel'],
        ];

        foreach ($books as $b) {
            Book::firstOrCreate(['judul' => $b['judul']], $b);
        }
    }
}
