-- Skema MySQL/MariaDB Perpustakaan API.
-- Sumber kebenaran tetap migration Laravel (backend/database/migrations).
-- File ini hanya referensi/dokumentasi; cara normal: `php artisan migrate:fresh --seed`.

CREATE DATABASE IF NOT EXISTS perpustakaan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE perpustakaan;

-- Pengguna/akun: mahasiswa. Password disimpan sebagai hash bcrypt.
CREATE TABLE students (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nim         VARCHAR(20)  NOT NULL UNIQUE,
  nama        VARCHAR(255) NOT NULL,
  password    VARCHAR(255) NOT NULL,
  created_at  TIMESTAMP NULL,
  updated_at  TIMESTAMP NULL
);

CREATE TABLE books (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  judul       VARCHAR(255) NOT NULL,
  penulis     VARCHAR(255) NOT NULL,
  created_at  TIMESTAMP NULL,
  updated_at  TIMESTAMP NULL
);

-- Transaksi peminjaman + status pengembalian.
-- Satu baris = satu kali peminjaman. Riwayat tidak dihapus saat buku dikembalikan.
CREATE TABLE borrowings (
  id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  book_id               BIGINT UNSIGNED NOT NULL,
  student_id            BIGINT UNSIGNED NOT NULL,
  tanggal_pinjam        DATETIME NOT NULL,
  tanggal_jatuh_tempo   DATETIME NOT NULL,            -- tanggal_pinjam + 7 hari (BR-03)
  tanggal_dikembalikan  DATETIME NULL,                -- NULL = belum dikembalikan
  status                VARCHAR(20) NOT NULL DEFAULT 'dipinjam',  -- 'dipinjam' | 'dikembalikan'
  -- = book_id selama dipinjam, NULL setelah dikembalikan. UNIQUE (NULL boleh berulang)
  -- menjamin satu buku hanya punya satu peminjaman aktif (BR-02), sekalipun request bersamaan.
  active_book_id        BIGINT UNSIGNED NULL UNIQUE,
  created_at            TIMESTAMP NULL,
  updated_at            TIMESTAMP NULL,
  CONSTRAINT fk_borrowings_book    FOREIGN KEY (book_id)    REFERENCES books(id)    ON DELETE CASCADE,
  CONSTRAINT fk_borrowings_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  INDEX idx_borrowings_student_status (student_id, status)
);

-- Tabel token Sanctum (dibuat otomatis oleh migration bawaan).
CREATE TABLE personal_access_tokens (
  id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tokenable_type VARCHAR(255) NOT NULL,
  tokenable_id   BIGINT UNSIGNED NOT NULL,
  name           TEXT NOT NULL,
  token          VARCHAR(64) NOT NULL UNIQUE,
  abilities      TEXT NULL,
  last_used_at   TIMESTAMP NULL,
  expires_at     TIMESTAMP NULL,
  created_at     TIMESTAMP NULL,
  updated_at     TIMESTAMP NULL,
  INDEX (tokenable_type, tokenable_id),
  INDEX (expires_at)
);
