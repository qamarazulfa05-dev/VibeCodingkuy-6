<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BorrowingController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// Semua route di file ini otomatis berawalan /api

// ---------- Publik ----------
Route::get('/health', function () {
    try {
        DB::select('select 1');
        $database = 'ok';
    } catch (\Throwable $e) {
        $database = 'down';
    }

    return response()->json([
        'service' => 'perpustakaan-api',
        'status' => 'ok',
        'database' => $database,
        'time' => now()->toIso8601ZuluString(),
    ]);
});

// /api/auth
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:20,1');

// /api/books
Route::get('/books', [BookController::class, 'index']);
Route::get('/books/{id}', [BookController::class, 'show'])->whereNumber('id');

// ---------- Wajib login: header "Authorization: Bearer <token>" ----------
Route::middleware('auth:sanctum')->group(function () {
    // /api/auth
    Route::get('/auth/verify', [AuthController::class, 'verify']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // /api/borrowings
    Route::get('/borrowings', [BorrowingController::class, 'index']);
    Route::post('/borrowings', [BorrowingController::class, 'store']);
    Route::match(['put', 'patch'], '/borrowings/{id}/return', [BorrowingController::class, 'returnBook'])
        ->whereNumber('id');
});
