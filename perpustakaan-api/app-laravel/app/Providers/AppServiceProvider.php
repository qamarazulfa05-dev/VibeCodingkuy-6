<?php

namespace App\Providers;

use App\Models\Student;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Pengguna aplikasi ini adalah mahasiswa (tabel `students`), bukan tabel `users` bawaan.
        // Sanctum memakai provider ini untuk memvalidasi pemilik token.
        config(['auth.providers.users.model' => Student::class]);
    }

    public function boot(): void
    {
        //
    }
}
