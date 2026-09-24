# Perpustakaan API (Laravel + MySQL) — 1 Service

Versi sederhana dari `perpustakaan-microservices`: **satu service Laravel**, data di **MySQL**,
semua fungsi diakses lewat **REST API** dengan **otorisasi token (Laravel Sanctum)**,
bisa langsung dites di **Postman**. Tanpa Docker.

Fungsi, aturan bisnis, dan format JSON **sama** dengan versi sebelumnya, jadi frontend lama tetap bisa dipakai.

| Sebelumnya | Sekarang |
|---|---|
| `auth-service` (Node, :4001) + `library-service` (Node, :4002) | 1 aplikasi Laravel (:8000) |
| File JSON (`students.json`, `books.json`, `loans.json`) | MySQL: tabel `students`, `books`, `loans` |
| JWT buatan sendiri, `library-service` memanggil `auth-service` | Sanctum Bearer token, dicek langsung di middleware `auth:sanctum` |
| Docker Compose | `php artisan serve` |

## Kebutuhan

PHP 8.2+, Composer, MySQL/MariaDB (XAMPP/Laragon juga bisa), Postman.
Ekstensi PHP: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`.

## Cara Menjalankan

```bash
# 1. Buat project Laravel baru
composer create-project laravel/laravel perpustakaan-api
cd perpustakaan-api

# 2. Pasang Sanctum + file api.php  (kalau ditanya "run migrations?" jawab: no)
php artisan install:api

# 3. Salin isi folder laravel-overlay/ ke dalam project (timpa file yang sama)
#    Linux/Mac/Git Bash:  cp -r /path/ke/laravel-overlay/. .
#    Windows: copy-paste isi folder laravel-overlay ke folder project, pilih "Replace"
```

4. Buat database di MySQL:
   ```sql
   CREATE DATABASE perpustakaan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
5. Buka `.env`, ganti blok `DB_*` sesuai isi `env-mysql.txt`.
6. Jalankan:
   ```bash
   php artisan migrate --seed
   php artisan serve
   ```
7. API aktif di `http://localhost:8000/api`. Frontend ada di `http://localhost:8000/app/`.

Akun uji (dari seeder): **NIM `230001` atau `230002`, password `password123`**.

## Endpoint

Semua request kirim `Accept: application/json`. Endpoint bertanda 🔒 wajib header `Authorization: Bearer <token>`.

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/api/health` | Status API + koneksi database |
| POST | `/api/auth/login` | Login `{nim, password}` → `{token, nim, nama, expiresIn}` |
| GET 🔒 | `/api/auth/verify` | Cek token, kembalikan `{valid, nim, nama}` (untuk aplikasi lain) |
| POST 🔒 | `/api/auth/logout` | Cabut token yang sedang dipakai |
| GET | `/api/books` | Daftar buku + status `tersedia`/`dipinjam` (publik) |
| GET 🔒 | `/api/loans` | Peminjaman milik mahasiswa yang login |
| POST 🔒 | `/api/loans` | Pinjam buku `{bookId}` |
| POST 🔒 | `/api/system/reset` | Hapus semua data peminjaman |

Kode status: `400` validasi, `401` tidak login/token salah, `404` buku tidak ada, `409` melanggar aturan bisnis, `201` peminjaman berhasil.
Semua error berbentuk `{ "error": "pesan" }`.

## Aturan Bisnis

| Kode | Aturan | Di mana dijaga |
|---|---|---|
| RB-01 | Login wajib; NIM angka ≥ 5 digit; password ≥ 6 karakter | `AuthController` + `auth:sanctum` |
| RB-02/06 | Hanya buku tersedia yang bisa dipinjam; buku yang dipinjam berstatus `dipinjam` | `LoanController`, `BookController` |
| RB-03 | Maksimal 3 buku aktif per mahasiswa | `LoanController` (dalam transaksi + `lockForUpdate`) |
| RB-04 | Satu buku tidak bisa dipinjam dua kali | `LoanController` + `UNIQUE(book_id)` di tabel `loans` |
| RB-05 | Lama pinjam 7 hari | `LoanController` |

## Tes di Postman

1. Import `postman/Perpustakaan-API.postman_collection.json`.
2. Variabel `base_url` sudah `http://localhost:8000`. Ubah kalau port berbeda.
3. Klik kanan collection → **Run collection**. 24 request berjalan berurutan dan punya assertion otomatis;
   token dari request login disimpan sendiri ke variabel `token`.

## Tes Otomatis (opsional)

```bash
php artisan test
```
Butuh ekstensi `pdo_sqlite` (bawaan konfigurasi tes Laravel), atau arahkan `phpunit.xml` ke database MySQL khusus tes.

## Isi Folder

```
laravel-overlay/
├── app/Http/Controllers/   Auth, Book, Loan, System (+ Controller dasar)
├── app/Models/             Student, Book, Loan
├── app/Providers/          AppServiceProvider (Student sebagai user Sanctum)
├── bootstrap/app.php       Error API selalu JSON, 401 seragam
├── database/migrations/    students, books, loans
├── database/seeders/       akun uji + 8 buku
├── routes/api.php          Semua route
├── tests/Feature/          Tes otomatis alur utama
└── public/app/             Frontend lama, sudah diarahkan ke 1 API
postman/                    Collection untuk Postman
env-mysql.txt               Contoh konfigurasi MySQL
```
