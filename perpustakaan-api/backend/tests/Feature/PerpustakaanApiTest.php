<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Pengujian otomatis (PHPUnit) untuk alur utama:
 * login -> katalog -> pinjam -> business rule -> kembalikan.
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

    private function login(string $nim = '230001', ?string $password = null): string
    {
        // Password akun uji: 230001 = 123456, 230002 = 654321 (lihat DatabaseSeeder)
        $password ??= $nim === '230002' ? '654321' : '123456';

        return $this->api('POST', '/api/auth/login', null, ['nim' => $nim, 'password' => $password])
            ->assertOk()
            ->json('token');
    }

    private function pinjam(string $token, int $bookId): TestResponse
    {
        return $this->api('POST', '/api/borrowings', $token, ['bookId' => $bookId]);
    }

    // ---------- FR-01 Login (T-01, T-02) ----------

    public function test_login_nim_kosong_ditolak(): void
    {
        $this->api('POST', '/api/auth/login', null, ['password' => '123456'])
            ->assertStatus(400)->assertJson(['error' => 'NIM wajib diisi.']);
    }

    public function test_login_nim_mengandung_huruf_ditolak(): void
    {
        $this->api('POST', '/api/auth/login', null, ['nim' => '23a001', 'password' => '123456'])
            ->assertStatus(400)->assertJson(['error' => 'NIM hanya boleh berisi angka.']);
    }

    public function test_login_nim_kurang_dari_5_digit_ditolak(): void
    {
        $this->api('POST', '/api/auth/login', null, ['nim' => '2300', 'password' => '123456'])
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

    public function test_login_nim_tidak_terdaftar_401(): void
    {
        $this->api('POST', '/api/auth/login', null, ['nim' => '999999', 'password' => '123456'])
            ->assertStatus(401);
    }

    public function test_login_benar_menerbitkan_token(): void
    {
        $this->api('POST', '/api/auth/login', null, ['nim' => '230001', 'password' => '123456'])
            ->assertOk()
            ->assertJsonStructure(['token', 'nim', 'nama', 'expiresIn'])
            ->assertJson(['nim' => '230001']);
    }

    public function test_verify_dengan_token_valid(): void
    {
        $this->api('GET', '/api/auth/verify', $this->login())
            ->assertOk()->assertJson(['valid' => true, 'nim' => '230001']);
    }

    // ---------- Otorisasi ----------

    public function test_endpoint_protected_tanpa_token_401(): void
    {
        $this->api('GET', '/api/borrowings')->assertStatus(401);
        $this->api('POST', '/api/borrowings', null, ['bookId' => 1])->assertStatus(401);
        $this->api('PATCH', '/api/borrowings/1/return')->assertStatus(401);
    }

    public function test_token_palsu_401(): void
    {
        $this->api('GET', '/api/borrowings', 'token-ngawur')->assertStatus(401);
    }

    public function test_setelah_logout_token_tidak_berlaku(): void
    {
        $token = $this->login();

        $this->api('POST', '/api/auth/logout', $token)->assertOk();
        $this->api('GET', '/api/borrowings', $token)->assertStatus(401);
    }

    // ---------- FR-02 Buku (T-03) ----------

    public function test_daftar_buku_publik_dan_semua_tersedia(): void
    {
        $res = $this->api('GET', '/api/books')->assertOk();

        $this->assertCount(8, $res->json());
        $this->assertSame('tersedia', $res->json('0.status'));
    }

    public function test_detail_buku_berhasil(): void
    {
        $this->api('GET', '/api/books/1')
            ->assertOk()
            ->assertJson(['id' => 1, 'judul' => 'Laskar Pelangi', 'status' => 'tersedia']);
    }

    public function test_detail_buku_tidak_ada_404(): void
    {
        $this->api('GET', '/api/books/999')
            ->assertStatus(404)->assertJson(['error' => 'Data buku tidak ditemukan.']);
    }

    // ---------- FR-03 Peminjaman (T-04, T-05, T-06) ----------

    public function test_pinjam_buku_tersedia_201_dan_jatuh_tempo_7_hari(): void
    {
        $token = $this->login();

        $res = $this->pinjam($token, 1)
            ->assertStatus(201)
            ->assertJsonPath('borrowing.idBuku', 1)
            ->assertJsonPath('borrowing.nimMahasiswa', '230001')
            ->assertJsonPath('borrowing.status', 'dipinjam')
            ->assertJsonPath('borrowing.tanggalDikembalikan', null);

        $pinjam = strtotime($res->json('borrowing.tanggalPinjam'));
        $jatuhTempo = strtotime($res->json('borrowing.tanggalJatuhTempo'));
        $this->assertEqualsWithDelta(7 * 86400, $jatuhTempo - $pinjam, 2);

        // status buku berubah menjadi dipinjam
        $this->assertSame('dipinjam', $this->api('GET', '/api/books/1')->json('status'));
    }

    public function test_pinjam_buku_yang_sama_dua_kali_409(): void
    {
        $token = $this->login();

        $this->pinjam($token, 1)->assertStatus(201);
        $this->pinjam($token, 1)->assertStatus(409)->assertJson(['error' => 'Buku sedang dipinjam.']);
    }

    public function test_buku_yang_dipinjam_mahasiswa_lain_tidak_bisa_dipinjam_409(): void
    {
        $this->pinjam($this->login('230001'), 1)->assertStatus(201);

        $this->pinjam($this->login('230002'), 1)->assertStatus(409);
    }

    public function test_pinjam_buku_ke_4_ditolak_409(): void
    {
        $token = $this->login();

        foreach ([1, 2, 3] as $id) {
            $this->pinjam($token, $id)->assertStatus(201);
        }

        $this->pinjam($token, 4)
            ->assertStatus(409)->assertJson(['error' => 'Maksimal peminjaman adalah 3 buku aktif.']);
    }

    public function test_book_id_tidak_valid_400_dan_tidak_ada_404(): void
    {
        $token = $this->login();

        $this->pinjam($token, 0)->assertStatus(400);
        $this->api('POST', '/api/borrowings', $token, ['bookId' => 'abc'])->assertStatus(400);
        $this->api('POST', '/api/borrowings', $token, [])->assertStatus(400);
        $this->pinjam($token, 999)->assertStatus(404);
    }

    public function test_daftar_peminjaman_hanya_milik_mahasiswa_yang_login(): void
    {
        $this->pinjam($this->login('230001'), 1)->assertStatus(201);

        $this->api('GET', '/api/borrowings', $this->login('230002'))
            ->assertOk()->assertJson(['jumlahAktif' => 0, 'maksimal' => 3, 'borrowings' => []]);
    }

    // ---------- FR-04 Pengembalian (T-07, T-08) ----------

    public function test_kembalikan_buku_mengubah_status_dan_ketersediaan(): void
    {
        $token = $this->login();
        $id = $this->pinjam($token, 1)->assertStatus(201)->json('borrowing.id');

        $this->assertSame('dipinjam', $this->api('GET', '/api/books/1')->json('status'));

        $res = $this->api('PATCH', "/api/borrowings/{$id}/return", $token)
            ->assertOk()
            ->assertJsonPath('borrowing.status', 'dikembalikan');

        $this->assertNotNull($res->json('borrowing.tanggalDikembalikan'));
        $this->assertSame('tersedia', $this->api('GET', '/api/books/1')->json('status'));
    }

    public function test_kembalikan_buku_yang_sudah_dikembalikan_409(): void
    {
        $token = $this->login();
        $id = $this->pinjam($token, 1)->json('borrowing.id');

        $this->api('PATCH', "/api/borrowings/{$id}/return", $token)->assertOk();
        $this->api('PATCH', "/api/borrowings/{$id}/return", $token)
            ->assertStatus(409)->assertJson(['error' => 'Buku sudah dikembalikan.']);
    }

    public function test_kembalikan_peminjaman_tidak_ada_404(): void
    {
        $this->api('PATCH', '/api/borrowings/999/return', $this->login())->assertStatus(404);
    }

    public function test_kembalikan_peminjaman_milik_orang_lain_403(): void
    {
        $id = $this->pinjam($this->login('230001'), 1)->json('borrowing.id');

        $this->api('PATCH', "/api/borrowings/{$id}/return", $this->login('230002'))->assertStatus(403);

        // buku tetap dipinjam
        $this->assertSame('dipinjam', $this->api('GET', '/api/books/1')->json('status'));
    }

    public function test_setelah_dikembalikan_buku_bisa_dipinjam_lagi_dan_riwayat_tersimpan(): void
    {
        $token = $this->login();
        $id = $this->pinjam($token, 1)->json('borrowing.id');
        $this->api('PATCH', "/api/borrowings/{$id}/return", $token)->assertOk();

        $this->pinjam($token, 1)->assertStatus(201);

        $res = $this->api('GET', '/api/borrowings', $token)->assertOk();
        $this->assertCount(2, $res->json('borrowings'));      // riwayat + aktif
        $this->assertSame(1, $res->json('jumlahAktif'));       // hanya yang belum dikembalikan
    }

    public function test_pengembalian_membebaskan_kuota_3_buku(): void
    {
        $token = $this->login();
        $ids = [];
        foreach ([1, 2, 3] as $bookId) {
            $ids[] = $this->pinjam($token, $bookId)->json('borrowing.id');
        }

        $this->pinjam($token, 4)->assertStatus(409);

        $this->api('PATCH', "/api/borrowings/{$ids[0]}/return", $token)->assertOk();

        $this->pinjam($token, 4)->assertStatus(201);
    }

    public function test_filter_status_daftar_peminjaman(): void
    {
        $token = $this->login();
        $id = $this->pinjam($token, 1)->json('borrowing.id');
        $this->pinjam($token, 2);
        $this->api('PATCH', "/api/borrowings/{$id}/return", $token)->assertOk();

        $this->assertCount(1, $this->api('GET', '/api/borrowings?status=dipinjam', $token)->json('borrowings'));
        $this->assertCount(1, $this->api('GET', '/api/borrowings?status=dikembalikan', $token)->json('borrowings'));
        $this->api('GET', '/api/borrowings?status=ngawur', $token)->assertStatus(400);
    }
}
