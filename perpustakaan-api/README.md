# Perpustakaan API

Backend API perpustakaan (UTS Vibe Coding): mahasiswa login, melihat buku, meminjam, mengembalikan, dan melihat
riwayat peminjaman. Satu backend service Laravel dengan database MySQL, diuji lewat Postman.

Arsitektur: `Client (Postman) → HTTP/JSON → Backend API (Laravel) → MySQL`

## Technology Stack

- PHP 8.3+, Laravel 13, Laravel Sanctum (Bearer token)
- MySQL / MariaDB
- Postman (collection) dan PHPUnit (tes otomatis)

## Kebutuhan Instalasi

- PHP 8.3+ dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`
- Composer
- MySQL atau MariaDB (XAMPP/Laragon juga bisa)
- Postman (atau Insomnia/Bruno)
- Untuk `php artisan test`: ekstensi `pdo_sqlite`

## Konfigurasi Database

1. Buat database kosong:
   ```sql
   CREATE DATABASE perpustakaan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
2. Salin konfigurasi lalu sesuaikan `DB_USERNAME` / `DB_PASSWORD` bila perlu:
   ```bash
   cd backend
   cp .env.example .env        # Windows: copy .env.example .env
   ```
   Nilai bawaan: host `127.0.0.1`, port `3306`, database `perpustakaan`, user `root`, password kosong.

Skema referensi ada di `database/schema.sql` dan diagram di `database/ERD.md`.

## Cara Menjalankan Backend

```bash
cd backend
composer install
php artisan key:generate
php artisan migrate:fresh --seed     # membuat tabel + akun uji + 8 buku
php artisan serve                    # http://localhost:8000
```

Cek: buka `http://localhost:8000/api/health` → `{"service":"perpustakaan-api","status":"ok","database":"ok",...}`

## Base URL API

`http://localhost:8000/api`

| Method | Endpoint | Login? |
|---|---|---|
| GET | `/api/health` | Tidak |
| POST | `/api/auth/login` | Tidak |
| GET | `/api/auth/verify` | Ya |
| POST | `/api/auth/logout` | Ya |
| GET | `/api/books` | Tidak |
| GET | `/api/books/{id}` | Tidak |
| POST | `/api/borrowings` | Ya |
| GET | `/api/borrowings` | Ya |
| PUT/PATCH | `/api/borrowings/{id}/return` | Ya |

Detail lengkap (request, response, status code): [`docs/API-CONTRACT.md`](docs/API-CONTRACT.md).
Endpoint bertanda "Ya" butuh header `Authorization: Bearer <token>`; semua request kirim `Accept: application/json`.

## Akun dan Data Uji untuk Login

| NIM | Password | Nama |
|---|---|---|
| `230001` | `123456` | Mahasiswa Satu |
| `230002` | `654321` | Mahasiswa Dua |

Seeder juga membuat 8 buku (ID 1–8), semuanya berstatus `tersedia`.

## Cara Menjalankan Pengujian API

**Postman**
1. `cd backend && php artisan migrate:fresh --seed && php artisan serve` (data harus dalam keadaan awal).
2. Import `api-test/collection.json` ke Postman. Variabel `base_url` bawaan `http://localhost:8000`.
3. Klik kanan collection → **Run collection** (jalankan berurutan, jangan diacak). Setiap request punya assertion otomatis;
   token dan ID peminjaman disimpan sendiri ke variabel collection. Folder E mengembalikan semua buku di akhir.
4. Jika run terhenti di tengah, jalankan ulang `php artisan migrate:fresh --seed` sebelum mengulang.
5. Simpan screenshot hasil Collection Runner ke folder `api-test/`.

**PHPUnit (otomatis, memakai SQLite in-memory)**
```bash
cd backend
php artisan test
```

## Struktur Proyek

```
perpustakaan-api/
├── docs/
│   ├── PRD.md
│   ├── API-CONTRACT.md
│   ├── CODE_REVIEW_LOG.md
│   └── PROMPTS.md
├── backend/                  Aplikasi Laravel (controller, model, migration, seeder, tes)
├── database/                 schema.sql + ERD.md
├── api-test/
│   └── collection.json       Postman collection
└── README.md
```

## Business Rule

| ID | Aturan | Dijaga di |
|---|---|---|
| BR-01 | Maksimal 3 buku aktif per mahasiswa | `BorrowingController::store` (transaksi + `lockForUpdate`) |
| BR-02 | Buku yang tidak tersedia tidak bisa dipinjam | `BorrowingController::store` + `UNIQUE(active_book_id)` |
| BR-03 | Jatuh tempo = tanggal pinjam + 7 hari | `BorrowingController::store` |
