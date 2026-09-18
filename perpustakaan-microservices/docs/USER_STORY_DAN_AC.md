# User Story & Acceptance Criteria

> Diambil dari dokumen Praktikum 2 – Requirement Engineering dengan AI (Kelompok 6),
> **tidak diubah**, sesuai ketentuan tugas poin 2: "Gunakan kembali User Story dan
> Acceptance Criteria yang telah dibuat."

## Studi Kasus

Perpustakaan kampus ingin memiliki prototype sistem peminjaman buku sederhana. Sistem digunakan
oleh mahasiswa untuk melihat buku dan melakukan peminjaman.

- Mahasiswa harus melakukan login.
- Mahasiswa dapat melihat daftar buku.
- Mahasiswa dapat melihat status ketersediaan buku.
- Mahasiswa dapat meminjam buku yang tersedia.
- Mahasiswa maksimal memiliki 3 buku aktif.
- Buku yang sedang dipinjam tidak dapat dipinjam mahasiswa lain.
- Lama peminjaman adalah 7 hari.
- Sistem harus menampilkan informasi peminjaman kepada mahasiswa.

## User Story

| ID | User Story |
|---|---|
| US-01 | Sebagai mahasiswa, saya ingin login ke sistem perpustakaan, sehingga dapat menggunakan layanan perpustakaan. |
| US-02 | Sebagai mahasiswa, saya ingin melihat daftar buku dan status ketersediaannya, sehingga dapat mengetahui buku yang bisa dipinjam. |
| US-03 | Sebagai mahasiswa, saya ingin melihat informasi peminjaman buku, sehingga dapat mengetahui buku yang sedang dipinjam dan batas waktu pengembaliannya. |

## Acceptance Criteria

| No | Given / Kondisi | When / Aksi | Then / Hasil |
|---|---|---|---|
| AC-01 | Mahasiswa sudah login | Membuka halaman daftar buku | Sistem menampilkan seluruh daftar buku beserta informasi statusnya. |
| AC-02 | Buku berstatus tersedia | Melihat daftar buku | Sistem menampilkan label status "Tersedia" dan tombol Pinjam aktif. |
| AC-03 | Buku sedang dipinjam | Melihat daftar buku | Sistem menampilkan label status "Dipinjam" dan tombol Pinjam nonaktif (disabled). |

## Aturan Bisnis (tetap dipertahankan pada versi microservice)

| Kode | Aturan | Diterapkan di |
|---|---|---|
| RB-01 | Mahasiswa harus login terlebih dahulu sebelum dapat melakukan peminjaman buku. | `auth-service` (penerbitan token) + `library-service` (middleware `requireAuth`) |
| RB-02 | Mahasiswa hanya dapat meminjam buku yang berstatus tersedia. | `library-service` (`POST /api/loans`) |
| RB-03 | Setiap mahasiswa hanya dapat memiliki maksimal 3 peminjaman aktif secara bersamaan. | `library-service` |
| RB-04 | Buku yang sedang dipinjam tidak dapat dipinjam oleh mahasiswa lain. | `library-service` |
| RB-05 | Setiap transaksi peminjaman memiliki periode peminjaman selama 7 hari sejak tanggal peminjaman. | `library-service` |
| RB-06 | Buku yang sedang dipinjam memiliki status "Dipinjam" dan tidak dapat dipinjam mahasiswa lain. | `library-service` (`GET /api/books`) |
| RB-07 | Sistem harus menampilkan informasi peminjaman kepada mahasiswa (buku yang dipinjam, tanggal peminjaman, batas pengembalian). | `library-service` (`GET /api/loans`) + frontend |
| RB-08 | Data disimpan secara persisten. | Pada versi sebelumnya: `localStorage`. Pada versi microservice: file JSON per service (`students.json`, `books.json`, `loans.json`), yang berperan sebagai database masing-masing service. |
