<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class Controller
{
    /**
     * Format error seragam untuk seluruh API: { "error": "pesan" }
     */
    protected function fail(string $message, int $status): JsonResponse
    {
        return response()->json(['error' => $message], $status);
    }
}
