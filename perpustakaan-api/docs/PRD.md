# PRD: API Perpustakaan Terintegrasi

Mata Kuliah Vibe Coding · UTS Kelompok · Waktu pengerjaan 7 hari

## 1. Informasi Proyek

| Item | Isi |
|---|---|
| Nama proyek | Perpustakaan API (Evolusi Tugas 3 menjadi Layanan Backend Terintegrasi) |
| Anggota (nama dan NIM) | [isi nama anggota 1 – NIM] |
| | [isi nama anggota 2 – NIM] |
| | [isi nama anggota 3 – NIM] |
| Versi dan tanggal | 1.0 · 15 Oktober 2026 |
| Link repository | [isi URL repository] |

## 2. Ringkasan

Backend API perpustakaan yang memungkinkan mahasiswa login, melihat katalog buku beserta ketersediaannya,
meminjam buku, dan mengembalikannya. Seluruh data (akun, buku, transaksi peminjaman) disimpan di database MySQL
dan semua endpoint diuji lewat Postman. Sistem ini adalah kelanjutan proyek Tugas 3 dalam satu backend service.

## 3. Tujuan

- Proyek Tugas 3 berkembang menjadi backend API yang terhubung ke database MySQL (semua data lewat tabel `students`, `books`, `borrowings`).
- Layanan peminjaman dan pengembalian buku berjalan sesuai BR-01 sampai BR-03 (maks. 3 buku aktif, buku tidak tersedia ditolak, jatuh tempo +7 hari).
- Semua 9 endpoint terdokumentasi di `docs/API-CONTRACT.md`, diuji lewat Postman collection (39 request) dan PHPUnit.
- Riwayat peminjaman tersimpan permanen dengan status pengembalian (`dipinjam` / `dikembalikan`).

## 4. Pengguna dan Peran

| Peran | Apa yang bisa dilakukan |
|---|---|
| Mahasiswa | Login, melihat buku, meminjam, mengembalikan, melihat riwayat peminjaman miliknya sendiri |
| Pengguna publik (tanpa login) | Hanya melihat daftar dan detail buku, serta health check |

Tidak ada peran admin (di luar scope UTS).

## 5. Ruang Lingkup

**Dikerjakan (in scope):**
- Autentikasi (login, verify, logout) dengan token Sanctum
- Katalog buku (daftar dan detail)
- Peminjaman dan pengembalian buku
- Database, API Contract, pengujian API

**Tidak dikerjakan (out of scope):**
- Frontend atau tampilan antarmuka (pengujian cukup lewat Postman). Frontend Tugas 3 tidak disertakan.
- Banyak server atau port (satu backend service di port 8000)
- Denda, notifikasi, perpanjangan pinjaman, peran admin
- Endpoint reset data (dihapus karena destruktif dan di luar kebutuhan UTS; lihat Code Review Log #3)

## 6. Technology Stack

Stack dilanjutkan dari Tugas 3 (tidak ada perubahan).

| Komponen | Pilihan kelompok | Alasan singkat |
|---|---|---|
| Backend/framework | Laravel 13 (PHP 8.3+) | Lanjutan Tugas 3; routing, migration, dan Eloquent memudahkan business rule dan transaksi |
| Autentikasi | Laravel Sanctum (Bearer token, 2 jam) | Sudah terintegrasi dengan Laravel; token disimpan di database |
| Database (wajib relasional) | MySQL / MariaDB | Mendukung foreign key, UNIQUE, dan `lockForUpdate` untuk konsistensi transaksi |
| Format API | HTTP/JSON | Ketentuan tugas |
| Alat uji API | Postman (collection di `api-test/collection.json`) + PHPUnit | Postman untuk uji manual/regresi endpoint; PHPUnit untuk uji otomatis |
| AI assistant yang dipakai | [isi, mis. Claude] | [isi alasan] |

## 7. Kebutuhan Fungsional

### FR-01: Login
- Pengguna login dengan akun (NIM + password) yang tersimpan di tabel `students`; password disimpan sebagai hash.
- **Kriteria penerimaan:**
  - Kredensial benar → 200 dan `token`.
  - NIM tidak terdaftar atau password salah → 401.
  - NIM kosong / bukan angka / < 5 digit, atau password < 6 karakter → 400 dengan pesan sesuai.
  - Endpoint 🔒 tanpa token atau dengan token setelah logout → 401.

### FR-02: Melihat Daftar dan Detail Buku
- Menampilkan daftar buku beserta status ketersediaan (`tersedia` / `dipinjam`).
- Menampilkan detail satu buku berdasarkan ID.
- **Kriteria penerimaan:**
  - `GET /api/books` → 200, berisi semua buku dengan field `id`, `judul`, `penulis`, `status`.
  - `GET /api/books/{id}` dengan ID ada → 200; ID tidak ada → 404.

### FR-03: Meminjam Buku
- Mahasiswa dapat meminjam buku yang tersedia.
- Status ketersediaan buku ikut diperbarui.
- Sistem mencatat tanggal pinjam dan menetapkan jatuh tempo 7 hari setelahnya.
- **Kriteria penerimaan:**
  - Buku tersedia → 201; `tanggalJatuhTempo − tanggalPinjam = 7 hari`; status buku menjadi `dipinjam`.
  - Buku sedang dipinjam → 409 (BR-02).
  - Mahasiswa sudah punya 3 buku aktif → 409 (BR-01).
  - `bookId` tidak valid → 400; buku tidak ada → 404; tanpa token → 401.

### FR-04: Mengembalikan Buku
- Mahasiswa dapat mengembalikan buku yang ia pinjam; status peminjaman menjadi `dikembalikan` dan `tanggalDikembalikan` terisi.
- Status ketersediaan buku kembali `tersedia`, dan kuota 3 buku mahasiswa bebas.
- **Kriteria penerimaan:**
  - Peminjaman milik sendiri dan masih aktif → 200; buku kembali `tersedia`.
  - Sudah dikembalikan → 409; ID tidak ada → 404; milik mahasiswa lain → 403; tanpa token → 401.
  - Setelah dikembalikan, buku bisa dipinjam lagi dan riwayat lama tetap ada.

### FR-05: Riwayat Peminjaman
- Mahasiswa melihat riwayat dan peminjaman aktif **miliknya sendiri**, dengan filter opsional `?status=`.
- **Kriteria penerimaan:**
  - 200 dengan `jumlahAktif` (hanya yang `dipinjam`), `maksimal`, dan daftar `borrowings` milik user yang login.
  - Mahasiswa lain tidak melihat data ini; `status` selain `dipinjam`/`dikembalikan` → 400.

## 8. Business Rule

| ID | Aturan |
|---|---|
| BR-01 | Satu mahasiswa maksimal 3 buku aktif (dipinjam dan belum dikembalikan). |
| BR-02 | Buku yang tidak tersedia tidak dapat dipinjam. |
| BR-03 | Jatuh tempo = tanggal pinjam + 7 hari. |

## 9. Model Data

Diagram relasi: `database/ERD.md`. Skema SQL: `database/schema.sql`. Sumber utama: migration di `backend/database/migrations/`.

| Tabel | Kolom utama | Relasi |
|---|---|---|
| `students` (pengguna/akun) | `id`, `nim` (unik), `nama`, `password` (hash) | 1 student → banyak borrowings |
| `books` | `id`, `judul`, `penulis` | 1 book → banyak borrowings |
| `borrowings` (transaksi + status pengembalian) | `id`, `book_id`, `student_id`, `tanggal_pinjam`, `tanggal_jatuh_tempo`, `tanggal_dikembalikan` (NULL = belum), `status`, `active_book_id` (UNIQUE) | FK ke `books` dan `students` |
| `personal_access_tokens` | token Sanctum | milik `students` |

Ketersediaan buku dihitung dari ada/tidaknya `borrowings` berstatus `dipinjam`, tidak disimpan terpisah.

## 10. Rancangan API Endpoint

| Layanan | Method | Endpoint | Fungsi | Perlu login? |
|---|---|---|---|---|
| Auth | POST | `/api/auth/login` | Login pengguna | Tidak |
| Auth | GET | `/api/auth/verify` | Cek token | Ya |
| Auth | POST | `/api/auth/logout` | Logout (cabut token) | Ya |
| Books | GET | `/api/books` | Daftar buku | Tidak |
| Books | GET | `/api/books/{id}` | Detail buku | Tidak |
| Borrowings | POST | `/api/borrowings` | Membuat peminjaman | Ya |
| Borrowings | GET | `/api/borrowings` | Daftar transaksi milik sendiri | Ya |
| Borrowings | PUT/PATCH | `/api/borrowings/{id}/return` | Mengembalikan buku | Ya |
| Sistem | GET | `/api/health` | Cek API + database | Tidak |

Detail request, response, dan status code: `docs/API-CONTRACT.md`.

## 11. Rencana Pengujian API

Skenario dijalankan lewat Postman collection (`api-test/collection.json`, kolom "Request") dan PHPUnit.

| ID | Skenario | Hasil yang diharapkan | Request Postman | Status |
|---|---|---|---|---|
| T-01 | Login dengan akun valid | 200, token diterbitkan | A7 | [isi lulus/gagal setelah run] |
| T-02 | Login dengan akun salah / input invalid | 401 (password salah); 400 (NIM/password tidak memenuhi validasi) | A2–A6 | [isi] |
| T-03 | Lihat daftar dan detail buku | 200; detail ID tidak ada → 404 | B1–B4 | [isi] |
| T-04 | Pinjam buku yang tersedia | 201, status `dipinjam`, jatuh tempo +7 hari | C4 | [isi] |
| T-05 | Pinjam buku yang tidak tersedia | 409 `Buku sedang dipinjam.` | C6, C11 | [isi] |
| T-06 | Pinjam buku ke-4 (melebihi batas 3) | 409 `Maksimal peminjaman adalah 3 buku aktif.` | C9 | [isi] |
| T-07 | Kembalikan buku | 200, status `dikembalikan`; ulang → 409; milik orang lain → 403; ID salah → 404 | D1–D5 | [isi] |
| T-08 | Cek status ketersediaan setelah pinjam dan kembali | `dipinjam` setelah pinjam, `tersedia` setelah kembali | C5, D6 | [isi] |

## 12. Rencana Penggunaan AI

Pola kerja: Requirement/Context → Prompt → Output AI → Review manusia → Testing → Iterasi.
Prompt lengkap disimpan di `docs/PROMPTS.md`.

| No | Pekerjaan | Context yang diberikan ke AI | Prompt disimpan di | Hasil review |
|---|---|---|---|---|
| 1 | Audit proyek Tugas 3 terhadap rubrik UTS dan perbaikan kode serta dokumen | PDF instruksi UTS + seluruh source proyek | `docs/PROMPTS.md` (Prompt 1) | Temuan di `docs/CODE_REVIEW_LOG.md` (8 temuan); [isi review kelompok] |
| 2 | [isi, mis. skema database tambahan] | [isi] | [isi] | [isi] |

## 13. Rencana Implementasi

| No | Tugas | Penanggung jawab | Target hari ke- | Selesai? |
|---|---|---|---|---|
| 1 | Isi PRD dan putuskan stack | [isi] | 1 | [isi] |
| 2 | Migration `borrowings`, model, seeder | [isi] | 2 | [isi] |
| 3 | Endpoint buku (daftar + detail) | [isi] | 2 | [isi] |
| 4 | Endpoint peminjaman + business rule BR-01..03 | [isi] | 3 | [isi] |
| 5 | Endpoint pengembalian | [isi] | 4 | [isi] |
| 6 | Tes PHPUnit | [isi] | 4 | [isi] |
| 7 | API Contract | [isi] | 5 | [isi] |
| 8 | Postman collection + screenshot bukti | [isi] | 5–6 | [isi] |
| 9 | Code Review Log + log prompt | [isi] | 6 | [isi] |
| 10 | README dan cek akhir checklist | [isi] | 7 | [isi] |

## 14. Definition of Done

Sebuah tugas dianggap selesai jika:
- Semua kriteria penerimaan terpenuhi.
- Sudah diuji di Postman, termasuk kasus gagal.
- Kode hasil AI sudah direview oleh anggota kelompok.
- Dokumentasi (API Contract dan README) sudah diperbarui.

## 15. Risiko dan Pertanyaan Terbuka

| Risiko / Pertanyaan | Rencana penanganan |
|---|---|
| Dua request peminjaman bersamaan melanggar batas 3 buku atau meminjam buku yang sama | Transaksi + `lockForUpdate` pada baris mahasiswa dan `UNIQUE(active_book_id)` di database |
| Semua anggota harus paham seluruh isi proyek | Sesi walkthrough kode bersama sebelum pengumpulan |
| Test Postman gagal di tengah dan meninggalkan data kotor | Jalankan `php artisan migrate:fresh --seed` sebelum run ulang |
| Ekstensi `pdo_sqlite` tidak aktif sehingga `php artisan test` gagal | Aktifkan ekstensi atau arahkan `phpunit.xml` ke database MySQL khusus tes |
