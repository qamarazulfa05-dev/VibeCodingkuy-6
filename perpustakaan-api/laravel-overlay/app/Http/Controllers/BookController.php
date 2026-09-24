<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\JsonResponse;

class BookController extends Controller
{
    /**
     * GET /api/books  (publik)
     * Daftar buku + status ketersediaan (AC-01, AC-02, AC-03).
     */
    public function index(): JsonResponse
    {
        $books = Book::withExists('loan')->orderBy('id')->get();

        return response()->json(
            $books->map(fn (Book $book) => [
                'id' => $book->id,
                'judul' => $book->judul,
                'penulis' => $book->penulis,
                'status' => $book->loan_exists ? 'dipinjam' : 'tersedia',
            ])->values()
        );
    }
}
