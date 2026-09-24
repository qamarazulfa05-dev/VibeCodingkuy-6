<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Student;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoanController extends Controller
{
    private const MAX_BUKU_AKTIF = 3;          // RB-03
    private const LAMA_PEMINJAMAN_HARI = 7;    // RB-05

    /**
     * GET /api/loans  (butuh token)
     * Hanya menampilkan peminjaman milik mahasiswa yang sedang login.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var Student $student */
        $student = $request->user();

        $loans = Loan::with('book')
            ->where('student_id', $student->id)
            ->orderBy('tanggal_pinjam')
            ->get()
            ->map(function (Loan $loan) use ($student) {
                $loan->setRelation('student', $student);

                return $loan->toApi();
            })
            ->values();

        return response()->json([
            'nim' => $student->nim,
            'jumlahAktif' => $loans->count(),
            'maksimal' => self::MAX_BUKU_AKTIF,
            'loans' => $loans,
        ]);
    }

    /**
     * POST /api/loans  (butuh token)
     * Body: { "bookId": 1 }
     * Menerapkan RB-02 s.d. RB-06.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var Student $student */
        $student = $request->user();

        $bookId = $request->input('bookId');

        if (! is_numeric($bookId) || (int) $bookId <= 0) {
            return $this->fail('bookId tidak valid.', 400);
        }

        $book = Book::find((int) $bookId);

        if (! $book) {
            return $this->fail('Data buku tidak ditemukan.', 404);
        }

        try {
            $result = DB::transaction(function () use ($student, $book) {
                // Kunci baris mahasiswa: dua request paralel dari mahasiswa yang sama
                // tidak bisa sama-sama lolos pengecekan batas 3 buku.
                Student::whereKey($student->id)->lockForUpdate()->first();

                // Buku yang sedang dipinjam (oleh siapa pun) tidak boleh dipinjam lagi.
                if (Loan::where('book_id', $book->id)->exists()) {
                    return ['status' => 409, 'error' => 'Buku sedang dipinjam.'];
                }

                // Maksimal 3 buku aktif per mahasiswa.
                if (Loan::where('student_id', $student->id)->count() >= self::MAX_BUKU_AKTIF) {
                    return [
                        'status' => 409,
                        'error' => 'Maksimal peminjaman adalah '.self::MAX_BUKU_AKTIF.' buku aktif.',
                    ];
                }

                $sekarang = now();

                $loan = Loan::create([
                    'book_id' => $book->id,
                    'student_id' => $student->id,
                    'tanggal_pinjam' => $sekarang,
                    'tanggal_kembali' => $sekarang->copy()->addDays(self::LAMA_PEMINJAMAN_HARI),
                ]);

                return ['loan' => $loan];
            });
        } catch (QueryException $e) {
            // Jaring pengaman: UNIQUE(book_id) menolak jika dua orang menekan "Pinjam" di detik yang sama.
            if (($e->errorInfo[1] ?? null) === 1062 || $e->getCode() === '23000') {
                return $this->fail('Buku sedang dipinjam.', 409);
            }
            throw $e;
        }

        if (isset($result['error'])) {
            return $this->fail($result['error'], $result['status']);
        }

        $loan = $result['loan'];
        $loan->setRelation('book', $book);
        $loan->setRelation('student', $student);

        return response()->json([
            'message' => 'Buku berhasil dipinjam.',
            'loan' => $loan->toApi(),
        ], 201);
    }
}
