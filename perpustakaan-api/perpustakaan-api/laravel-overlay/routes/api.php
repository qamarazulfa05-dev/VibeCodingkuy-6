<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\SystemController;
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

Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::get('/books', [BookController::class, 'index']);

// ---------- Wajib login: header "Authorization: Bearer <token>" ----------
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/verify', [AuthController::class, 'verify']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/loans', [LoanController::class, 'index']);
    Route::post('/loans', [LoanController::class, 'store']);

    Route::post('/system/reset', [SystemController::class, 'reset']);
});
