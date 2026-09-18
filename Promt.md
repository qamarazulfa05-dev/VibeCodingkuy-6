### Prompt 1 — Instruksi Tugas Utama
```
Mengembangkan proyek kelompok sebelumnya menjadi aplikasi berbasis microservice dengan
memanfaatkan AI Coding Tool.

Ketentuan:
1. Gunakan proyek yang telah dikerjakan pada pertemuan sebelumnya.
2. Gunakan kembali User Story dan Acceptance Criteria yang telah dibuat.
3. Kembangkan architecture proyek menjadi minimal 2 service/microservice.
4. Technology boleh dikembangkan/diubah sesuai kebutuhan proyek.
5. Gunakan AI Coding Tool untuk membantu proses pengembangan.
6. Minimal 1 alur fitur yang berjalan dengan melibatkan service yang dibuat.
7. Setiap service harus memiliki fungsi yang jelas.
8. Antarservice harus dapat berkomunikasi menggunakan API.
9. Periksa dan perbaiki kode yang dihasilkan AI, jangan langsung dipakai tanpa pemeriksaan.

[terlampir: perpustakaan_zip.zip (proyek lama) dan dokumen requirement kelompok]
```

### Prompt 2 — Analisis Proyek Lama
```
Coba pelajari lagi file index.html, style.css, dan script.js dari proyek lama, plus User Story sama Acceptance Criteria di dokumen Praktikum 2. Rangkum semua aturan bisnisnya (RB-01 sampai RB-08) dan fitur yang udah jalan sekarang, sebelum kita mulai ngerancang arsitektur barunya.
```

### Prompt 3 — Rancang Arsitektur Microservice
```
Sekarang, tolong bagi aplikasi ini jadi minimal 2 microservice. Pastiin masing-masing punya tugas yang beda dan saling ngobrol lewat API. Jangan sampai ada aturan bisnis lama yang hilang ya (kayak maksimal pinjam 3 buku, gak boleh pinjam buku yang sama dua kali, batas pinjam 7 hari, dll).
```

### Prompt 4 — Implementasi auth-service
```
Bikinin auth-service pakai Node.js + Express. Tugasnya khusus nanganin login mahasiswa (NIM & password) dan ngeluarin token, plus sediain endpoint buat verifikasi token-nya. Validasi inputnya samain kayak versi kemarin ya: NIM harus angka (minimal 5 digit) dan password minimal 6 karakter.
```

### Prompt 5 — Implementasi library-service
```
Selanjutnya, buat library-service pakai Node.js + Express buat ngelola data buku dan transaksi peminjaman. Service ini gak boleh nyimpan password; buat ngecek siapa yang lagi login, dia harus nembak/manggil auth-service lewat HTTP. Terapin semua aturan bisnis RB-02 sampai RB-06 di sini.
```

### Prompt 6 — Integrasi Frontend
```
Sekarang ubah script.js supaya pemanggilan datanya nembak ke auth-service dan library-service pakai fetch(). Jangan pakai localStorage lagi buat nyimpan data buku/peminjaman. Tapi buat tampilan (style.css dan struktur HTML-nya), pertahanin biar tetep sama persis kayak sebelumnya.
```

### Prompt 7 — Uji Coba
```
Jalanin kedua service-nya, terus tes alurnya dari awal: login -> lihat katalog buku -> coba pinjam buku -> tes pinjam buku yang sama lagi (harus gagal) -> tes pinjam buku ke-4 (harus nolak karena maksimal cuma 3) -> terus tes reset sistem. Tolong tampilin hasil pengujian di tiap step-nya ya.
```

### Prompt 8 — Dokumentasi
```
Terakhir, tolong siapin dokumentasinya: buat file README, diagram arsitektur sebelum vs sesudah (pakai format mermaid), catatan penggunaan AI Coding Tool beserta daftar prompt-nya, plus file docker-compose biar semuanya bisa dijalani dengan gampang. Siapin struktur proyeknya biar rapi dan siap di-push ke repository GitHub publik.
```

## 4. Masalah / Kesalahan yang Ditemukan dari Hasil AI (dan Perbaikannya)

Sesuai ketentuan poin 9 (kode AI tidak langsung dipakai tanpa diperiksa), berikut hal-hal yang
ditemukan saat meninjau dan menguji kode hasil AI, beserta perbaikannya:

