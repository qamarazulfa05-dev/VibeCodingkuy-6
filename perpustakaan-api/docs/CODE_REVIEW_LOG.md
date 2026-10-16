# Code Review Log

Review atas kode backend hasil Tugas 3 (dibantu AI) sebelum dikembangkan menjadi UTS.
Setiap temuan sudah diperbaiki dan divalidasi lewat PHPUnit (`backend/tests/Feature/PerpustakaanApiTest.php`)
dan Postman collection (`api-test/collection.json`).

| No. | Bagian | Aspek | Temuan | Perbaikan / Keputusan |
|---|---|---|---|---|
| 1 | `loans` migration + `LoanController` | Correctness | Tidak ada fitur pengembalian. Selain itu `UNIQUE(book_id)` pada tabel `loans` memaksa satu buku hanya punya satu baris selamanya, sehingga riwayat peminjaman tidak mungkin disimpan dan "status pengembalian" (wajib di UTS) tidak ada. | Tabel diganti `borrowings` dengan kolom `status`, `tanggal_dikembalikan`, dan `active_book_id` (UNIQUE, nullable). Kolom terakhir terisi saat dipinjam dan di-NULL-kan saat dikembalikan, jadi riwayat tersimpan dan aturan "satu buku satu peminjaman aktif" tetap dijaga database. Ditambah endpoint `PATCH /api/borrowings/{id}/return`. |
| 2 | `LoanController::index` | Correctness | `jumlahAktif` dihitung dari `$loans->count()` (jumlah semua baris). Begitu riwayat ikut tersimpan, angka ini salah dan bisa menampilkan lebih dari 3 buku "aktif". | `jumlahAktif` dihitung terpisah dengan `Borrowing::aktif()`. Filter `?status=` ditambahkan dan divalidasi (400 untuk nilai asing). Dites di `test_filter_status_daftar_peminjaman` dan `test_setelah_dikembalikan_buku_bisa_dipinjam_lagi_dan_riwayat_tersimpan`. |
| 3 | `SystemController::reset` + route `/api/system/reset` | Correctness / keamanan | Endpoint ini menghapus seluruh peminjaman semua mahasiswa dan bisa dipanggil oleh mahasiswa mana pun yang login. Fitur ini juga tidak ada di requirement UTS (di luar scope). | Endpoint, controller, dan tes `reset` dihapus. Postman collection tidak lagi bergantung padanya: collection membersihkan datanya sendiri dengan mengembalikan semua buku di folder E. |
| 4 | `LoanController::store` (blok `catch QueryException`) | Correctness | Kondisi `$e->getCode() === '23000'` menangkap semua pelanggaran integritas (termasuk foreign key), lalu selalu melaporkannya sebagai "Buku sedang dipinjam" (409). Kesalahan lain jadi tersamarkan. | Pengecekan dipersempit: hanya kode MySQL `1062` atau pesan yang menyebut `active_book_id` (UNIQUE) yang dipetakan ke 409; selain itu exception dilempar ulang. |
| 5 | `Loan` model + migration (kolom `tanggal_kembali`) | Readability | `tanggal_kembali` sebenarnya berisi tanggal jatuh tempo, sehingga menyesatkan begitu ada tanggal pengembalian sungguhan. | Diganti `tanggal_jatuh_tempo` (jatuh tempo) dan `tanggal_dikembalikan` (tanggal kembali sebenarnya). Field JSON ikut diganti: `tanggalJatuhTempo`, `tanggalDikembalikan`. |
| 6 | `DatabaseSeeder` | Maintainability | Password akun uji di seeder, README, Postman, dan tes tidak seragam, sehingga orang yang mengikuti README bisa gagal login. | Semua disatukan mengikuti seeder proyek: `230001` = `123456`, `230002` = `654321`. Akun uji dicantumkan di README. |
| 7 | Penamaan `loans` / `LoanController` / `/api/loans` | Coding Standard | Istilah di kode (`loans`) berbeda dari PRD dan instruksi UTS (`borrowings`), padahal endpoint harus konsisten dengan dokumen. | Semua diseragamkan menjadi `borrowings`: tabel, model `Borrowing`, `BorrowingController`, route `/api/borrowings`. |
| 8 | `BookController` | Correctness | Tidak ada endpoint detail buku (`GET /api/books/{id}`), padahal wajib ada di UTS. Logika status juga hanya hidup di controller. | Ditambah `show()` (404 jika ID tidak ada). Bentuk JSON buku dipindah ke `Book::toApi()` agar `index` dan `show` konsisten. |

## Catatan validasi

- Tes PHPUnit baru mencakup semua temuan di atas (jalankan: `cd backend && php artisan test`).
- Hasil run Postman: lihat `api-test/` (isi screenshot Collection Runner di sana).
