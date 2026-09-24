<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Pengujian otomatis untuk alur utama: login -> katalog -> pinjam -> aturan bisnis -> reset.
 * Jalankan dengan: php artisan test
 */
class PerpustakaanApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function api(string $method, string $uri, ?string $token = null, array $data = []): TestResponse
    {
        // Sanctum menyimpan user di memori selama satu test; reset agar token berbeda dibaca ulang.
        $this->app['auth']->forgetGuards();

        $headers = $token ? ['Authorization' => 'Bearer '.$token] : [];

        return $this->json($method, $uri, $data, $headers);
    }

    private function login(string $nim = '230001', string $password = 'password123'): string
    {
        return $this->api('POST', '/api/auth/login', null, ['nim' => $nim, 'password' => $password])
            ->assertOk()
            ->json('token');
    }

    // ---------- Login ----------

    public function test_login_nim_kosong_ditolak(): void
    {
        $this->api('POST', '/api/auth/login', null, ['password' => 'password123'])
            ->assertStatus(400)->assertJson(['error' => 'NIM wajib diisi.']);
    }

    public function test_login_nim_mengandung_huruf_ditolak(): void
    {
        $this->api('POST', '/api/auth/login', null, ['nim' => '23a001', 'password' => 'password123'])
            ->assertStatus(400)->assertJson(['error' => 'NIM hanya boleh berisi angka.']);
    }

    public function test_login_nim_kurang_dari_5_digit_ditolak(): void
    {
        $this->api('POST', '/api/auth/login', null, ['nim' => '2300', 'password' => 'password123'])
            ->assertStatus(400)->assertJson(['error' => 'NIM harus terdiri dari minimal 5 angka.']);
    }

    public function test_login_password_kurang_dari_6_karakter_ditolak(): void
    {
        $this->api('POST', '/api/auth/login', null, ['nim' => '230001', 'password' => '12345'])
            ->assertStatus(400)->assertJson(['error' => 'Password harus terdiri dari minimal 6 karakter.']);
    }

    public function test_login_password_salah_401(): void
    {
        $this->api('POST', '/api/auth/login', null, ['nim' => '230001', 'password' => 'salahbanget'])
            ->assertStatus(401)->assertJson(['error' => 'NIM atau Password salah.']);
    }

    public function test_login_benar_menerbitkan_token(): void
    {
        $this->api('POST', '/api/auth/login', null, ['nim' => '230001', 'password' => 'password123'])
            ->assertOk()
            ->assertJsonStructure(['token', 'nim', 'nama', 'expiresIn'])
            ->assertJson(['nim' => '230001']);
    }

    public function test_verify_dengan_token_valid(): void
    {
        $token = $this->login();

        $this->api('GET', '/api/auth/verify', $token)
            ->assertOk()->assertJson(['valid' => true, 'nim' => '230001']);
    }

    // ---------- Otorisasi ----------

    public function test_endpoint_protected_tanpa_token_401(): void
    {
        $this->api('GET', '/api/loans')->assertStatus(401);
    }

    public function test_pinjam_tanpa_token_401(): void
    {
        $this->api('POST', '/api/loans', null, ['bookId' => 1])->assertStatus(401);
    }

    public function test_token_palsu_401(): void
    {
        $this->api('GET', '/api/loans', 'token-ngawur')->assertStatus(401);
    }

    public function test_setelah_logout_token_tidak_berlaku(): void
    {
        $token = $this->login();

        $this->api('POST', '/api/auth/logout', $token)->assertOk();
        $this->api('GET', '/api/loans', $token)->assertStatus(401);
    }

    // ---------- Buku & peminjaman ----------

    public function test_daftar_buku_publik_dan_semua_tersedia(): void
    {
        $res = $this->api('GET', '/api/books')->assertOk();

        $this->assertCount(8, $res->json());
        $this->assertSame('tersedia', $res->json('0.status'));
    }

    public function test_pinjam_buku_tersedia_201_dan_batas_7_hari(): void
    {
        $token = $this->login();

        $res = $this->api('POST', '/api/loans', $token, ['bookId' => 1])
            ->assertStatus(201)
            ->assertJsonPath('loan.idBuku', 1)
            ->assertJsonPath('loan.nimMahasiswa', '230001');

        $pinjam = strtotime($res->json('loan.tanggalPinjam'));
        $kembali = strtotime($res->json('loan.tanggalKembali'));
        $this->assertEqualsWithDelta(7 * 86400, $kembali - $pinjam, 2);

        // status buku berubah menjadi dipinjam (RB-06)
        $this->assertSame('dipinjam', $this->api('GET', '/api/books')->json('0.status'));
    }

    public function test_pinjam_buku_yang_sama_dua_kali_409(): void
    {
        $token = $this->login();

        $this->api('POST', '/api/loans', $token, ['bookId' => 1])->assertStatus(201);
        $this->api('POST', '/api/loans', $token, ['bookId' => 1])
            ->assertStatus(409)->assertJson(['error' => 'Buku sedang dipinjam.']);
    }

    public function test_buku_yang_dipinjam_mahasiswa_lain_tidak_bisa_dipinjam_409(): void
    {
        $this->api('POST', '/api/loans', $this->login('230001'), ['bookId' => 1])->assertStatus(201);

        $this->api('POST', '/api/loans', $this->login('230002'), ['bookId' => 1])
            ->assertStatus(409);
    }

    public function test_pinjam_buku_ke_4_ditolak_409(): void
    {
        $token = $this->login();

        foreach ([1, 2, 3] as $id) {
            $this->api('POST', '/api/loans', $token, ['bookId' => $id])->assertStatus(201);
        }

        $this->api('POST', '/api/loans', $token, ['bookId' => 4])
            ->assertStatus(409)->assertJson(['error' => 'Maksimal peminjaman adalah 3 buku aktif.']);
    }

    public function test_book_id_tidak_valid_400_dan_tidak_ada_404(): void
    {
        $token = $this->login();

        $this->api('POST', '/api/loans', $token, ['bookId' => 'abc'])->assertStatus(400);
        $this->api('POST', '/api/loans', $token, ['bookId' => 999])->assertStatus(404);
    }

    public function test_loans_hanya_milik_mahasiswa_yang_login(): void
    {
        $this->api('POST', '/api/loans', $this->login('230001'), ['bookId' => 1])->assertStatus(201);

        $this->api('GET', '/api/loans', $this->login('230002'))
            ->assertOk()->assertJson(['jumlahAktif' => 0, 'maksimal' => 3]);
    }

    // ---------- Reset ----------

    public function test_reset_menghapus_semua_peminjaman(): void
    {
        $token = $this->login();

        $this->api('POST', '/api/loans', $token, ['bookId' => 1])->assertStatus(201);
        $this->api('POST', '/api/system/reset', $token)->assertOk();

        $this->assertSame('tersedia', $this->api('GET', '/api/books')->json('0.status'));
        $this->api('GET', '/api/loans', $token)->assertJson(['jumlahAktif' => 0]);
    }

    public function test_reset_tanpa_token_401(): void
    {
        $this->api('POST', '/api/system/reset')->assertStatus(401);
    }
}
