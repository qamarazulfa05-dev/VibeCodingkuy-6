<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    private const TOKEN_LIFETIME_HOURS = 2;

    /**
     * POST /api/auth/login
     * Validasi sama seperti versi sebelumnya (RB-01):
     *  - NIM wajib, hanya angka, minimal 5 digit
     *  - Password wajib, minimal 6 karakter
     */
    public function login(Request $request): JsonResponse
    {
        $nim = $request->input('nim');
        $password = $request->input('password');

        if (is_int($nim)) {
            $nim = (string) $nim; // Postman sering mengirim NIM sebagai angka
        }

        if (! is_string($nim) || trim($nim) === '') {
            return $this->fail('NIM wajib diisi.', 400);
        }

        $nim = trim($nim);

        if (! preg_match('/^[0-9]+$/', $nim)) {
            return $this->fail('NIM hanya boleh berisi angka.', 400);
        }

        if (strlen($nim) < 5) {
            return $this->fail('NIM harus terdiri dari minimal 5 angka.', 400);
        }

        if (! is_string($password) || mb_strlen($password) < 6) {
            return $this->fail('Password harus terdiri dari minimal 6 karakter.', 400);
        }

        $student = Student::where('nim', $nim)->first();

        if (! $student || ! Hash::check($password, $student->password)) {
            return $this->fail('NIM atau Password salah.', 401);
        }

        $token = $student
            ->createToken('api-token', ['*'], now()->addHours(self::TOKEN_LIFETIME_HOURS))
            ->plainTextToken;

        return response()->json([
            'token' => $token,
            'nim' => $student->nim,
            'nama' => $student->nama,
            'expiresIn' => self::TOKEN_LIFETIME_HOURS.'h',
        ]);
    }

    /**
     * GET /api/auth/verify  (butuh token)
     * Dipakai aplikasi lain untuk memastikan token masih valid dan mengetahui identitas pemiliknya.
     */
    public function verify(Request $request): JsonResponse
    {
        $student = $request->user();

        return response()->json([
            'valid' => true,
            'nim' => $student->nim,
            'nama' => $student->nama,
        ]);
    }

    /**
     * POST /api/auth/logout  (butuh token)
     * Mencabut token yang sedang dipakai.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout berhasil.']);
    }
}
