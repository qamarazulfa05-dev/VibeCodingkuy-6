<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Student;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BorrowingController extends Controller
{
    private const MAX_BUKU_AKTIF = 3;          // BR-01
    private const LAMA_PEMINJAMAN_HARI = 7;    // BR-03

    /**
     * GET /api/borrowings  (butuh token)
     * Riwayat + peminjaman aktif milik mahasiswa yang sedang login.
     * Filter opsional: ?status=dipinjam atau ?status=dikembalikan
     */
    public function index(Request $request): JsonResponse
    {
        /** @var Student $student */
        $student = $request->user();

        $status = $request->query('status');

        if ($status !== null && ! in_array($status, [Borrowing::STATUS_DIPINJAM, Borrowing::STATUS_DIKEMBALIKAN], true)) {
            return $this->fail("Parameter status harus 'dipinjam' atau 'dikembalikan'.", 400);
        }

        $query = Borrowing::with('book')->where('student_id', $student->id);

        if ($status !== null) {
            $query->where('status', $status);
        }

        $borrowings = $query
            ->orderBy('tanggal_pinjam')
            ->orderBy('id')
            ->get()
            ->map(function (Borrowing $borrowing) use ($student) {
                $borrowing->setRelation('student', $student);

                return $borrowing->toApi();
            })
            ->values();

        return response()->json([
            'nim' => $student->nim,
            'jumlahAktif' => Borrowing::aktif()->where('student_id', $student->id)->count(),
            'maksimal' => self::MAX_BUKU_AKTIF,
            'borrowings' => $borrowings,
        ]);
    }

    /**
     * POST /api/borrowings  (butuh token)
     * Body: { "bookId": 1 }
     * Menerapkan BR-01, BR-02, BR-03 (FR-03).
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

                // BR-02: buku yang sedang dipinjam (oleh siapa pun) tidak boleh dipinjam lagi.
                if (Borrowing::aktif()->where('book_id', $book->id)->exists()) {
                    return ['status' => 409, 'error' => 'Buku sedang dipinjam.'];
                }

                // BR-01: maksimal 3 buku aktif per mahasiswa.
                if (Borrowing::aktif()->where('student_id', $student->id)->count() >= self::MAX_BUKU_AKTIF) {
                    return [
                        'status' => 409,
                        'error' => 'Maksimal peminjaman adalah '.self::MAX_BUKU_AKTIF.' buku aktif.',
                    ];
                }

                $sekarang = now();

                $borrowing = Borrowing::create([
                    'book_id' => $book->id,
                    'student_id' => $student->id,
                    'tanggal_pinjam' => $sekarang,
                    // BR-03: jatuh tempo = tanggal pinjam + 7 hari
                    'tanggal_jatuh_tempo' => $sekarang->copy()->addDays(self::LAMA_PEMINJAMAN_HARI),
                    'status' => Borrowing::STATUS_DIPINJAM,
                    'active_book_id' => $book->id,
                ]);

                return ['borrowing' => $borrowing];
            });
        } catch (QueryException $e) {
            // Jaring pengaman: UNIQUE(active_book_id) menolak jika dua orang menekan "Pinjam" di detik yang sama.
            if (($e->errorInfo[1] ?? null) === 1062 || str_contains($e->getMessage(), 'active_book_id')) {
                return $this->fail('Buku sedang dipinjam.', 409);
            }
            throw $e;
        }

        if (isset($result['error'])) {
            return $this->fail($result['error'], $result['status']);
        }

        $borrowing = $result['borrowing'];
        $borrowing->setRelation('book', $book);
        $borrowing->setRelation('student', $student);

        return response()->json([
            'message' => 'Buku berhasil dipinjam.',
            'borrowing' => $borrowing->toApi(),
        ], 201);
    }

    /**
     * PUT|PATCH /api/borrowings/{id}/return  (butuh token)
     * Mengembalikan buku: status peminjaman -> 'dikembalikan', buku kembali 'tersedia' (FR-04).
     */
    public function returnBook(Request $request, int $id): JsonResponse
    {
        /** @var Student $student */
        $student = $request->user();

        $borrowing = Borrowing::find($id);

        if (! $borrowing) {
            return $this->fail('Data peminjaman tidak ditemukan.', 404);
        }

        if ((int) $borrowing->student_id !== (int) $student->id) {
            return $this->fail('Peminjaman ini bukan milik Anda.', 403);
        }

        $result = DB::transaction(function () use ($id) {
            // Kunci baris supaya dua request "kembalikan" bersamaan tidak lolos dua-duanya.
            $locked = Borrowing::whereKey($id)->lockForUpdate()->first();

            if ($locked->status === Borrowing::STATUS_DIKEMBALIKAN) {
                return ['error' => 'Buku sudah dikembalikan.'];
            }

            $locked->update([
                'status' => Borrowing::STATUS_DIKEMBALIKAN,
                'tanggal_dikembalikan' => now(),
                'active_book_id' => null, // melepas kunci UNIQUE agar buku bisa dipinjam lagi
            ]);

            return ['borrowing' => $locked];
        });

        if (isset($result['error'])) {
            return $this->fail($result['error'], 409);
        }

        $returned = $result['borrowing'];
        $returned->load('book');
        $returned->setRelation('student', $student);

        return response()->json([
            'message' => 'Buku berhasil dikembalikan.',
            'borrowing' => $returned->toApi(),
        ]);
    }
}
