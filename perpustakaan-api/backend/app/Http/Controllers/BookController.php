<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\JsonResponse;

class BookController extends Controller
{
    /**
     * GET /api/books  (publik)
     * Daftar buku + status ketersediaan (FR-02).
     */
    public function index(): JsonResponse
    {
        $books = Book::withExists('activeBorrowing')->orderBy('id')->get();

        return response()->json($books->map(fn (Book $book) => $book->toApi())->values());
    }

    /**
     * GET /api/books/{id}  (publik)
     * Detail satu buku. ID tidak ada -> 404 (FR-02).
     */
    public function show(int $id): JsonResponse
    {
        $book = Book::withExists('activeBorrowing')->find($id);

        if (! $book) {
            return $this->fail('Data buku tidak ditemukan.', 404);
        }

        return response()->json($book->toApi());
    }
}
