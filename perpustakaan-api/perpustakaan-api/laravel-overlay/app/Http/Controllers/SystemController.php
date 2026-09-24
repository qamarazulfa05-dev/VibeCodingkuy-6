<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use Illuminate\Http\JsonResponse;

class SystemController extends Controller
{
    /**
     * POST /api/system/reset  (butuh token)
     * Setara fitur "Restart Sistem": menghapus seluruh data peminjaman.
     * Data buku dan mahasiswa tidak disentuh.
     */
    public function reset(): JsonResponse
    {
        Loan::query()->delete();

        return response()->json([
            'message' => 'Sistem berhasil direset. Semua buku kembali tersedia.',
        ]);
    }
}
