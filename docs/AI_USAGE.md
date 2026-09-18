# Dokumentasi Penggunaan AI Coding Tool

## 1. AI Coding Tool yang Digunakan

**Claude (Anthropic)**, digunakan langsung dalam mode agentic coding — AI diberi akses ke
proyek awal (hasil Praktikum 2: `index.html`, `style.css`, `script.js`), lalu diminta menganalisis,
merancang ulang arsitektur menjadi microservice, menulis kode, **menjalankan dan menguji kode
tersebut** (bukan hanya menghasilkan teks kode), dan menulis dokumentasi.

## 2. Bagaimana AI Membantu Proses Pengembangan

1. **Analisis proyek lama** — AI membaca `index.html`, `style.css`, `script.js`, dan dokumen
   requirement (`Vibe_Coding_kel_6.docx`) untuk memahami User Story, Acceptance Criteria, dan
   seluruh aturan bisnis (RB-01 s.d. RB-08) yang sudah ditetapkan sebelumnya, supaya tidak
   dibuat ulang dari nol.
2. **Merancang pemecahan menjadi microservice** — AI mengusulkan pemisahan tanggung jawab:
   autentikasi (`auth-service`) dipisah dari data buku & peminjaman (`library-service`), lengkap
   dengan alasan (prinsip *single responsibility* pada microservice).
3. **Generate kode backend** — AI menuliskan kode Express.js untuk kedua service, termasuk
   validasi input, JWT, hashing password, dan middleware yang memanggil service lain lewat API.
4. **Generate ulang frontend** — AI mengadaptasi `script.js` agar memanggil REST API
   (`fetch`) alih-alih `localStorage`, sambil mempertahankan tampilan (`style.css`) dan struktur
   halaman aslinya.
5. **Menjalankan pengujian nyata** — AI menjalankan kedua service, melakukan `curl` untuk
   login, ambil daftar buku, pinjam buku, uji batas maksimal 3 buku, uji buku yang sudah
   dipinjam, dan reset sistem — untuk memastikan kode benar-benar berjalan, bukan sekadar
   ditulis.
6. **Menulis dokumentasi** — README, diagram arsitektur, dan dokumen ini.

## 3. Prompt yang Digunakan

Berikut prompt-prompt utama (disusun ulang secara ringkas sesuai urutan proses) yang diberikan
kepada AI:

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
Baca ulang index.html, style.css, dan script.js dari proyek lama, serta User Story dan
Acceptance Criteria pada dokumen Praktikum 2. Ringkas seluruh aturan bisnis (RB-01 s.d. RB-08)
dan fitur yang sudah ada, sebelum merancang arsitektur baru.
```

### Prompt 3 — Rancang Arsitektur Microservice
```
Pecah aplikasi ini menjadi minimal 2 microservice yang masing-masing punya tanggung jawab
jelas dan berkomunikasi lewat API. Jangan hilangkan aturan bisnis yang sudah ada (maksimal 3
buku aktif, buku tidak boleh dipinjam dua kali, masa pinjam 7 hari, dst).
```

### Prompt 4 — Implementasi auth-service
```
Buatkan auth-service dengan Node.js + Express yang menangani login mahasiswa (NIM & password)
dan menerbitkan token, serta endpoint untuk memverifikasi token tersebut. Gunakan validasi input
yang sama seperti versi sebelumnya (NIM harus angka, minimal 5 digit; password minimal 6
karakter).
```

### Prompt 5 — Implementasi library-service
```
Buatkan library-service dengan Node.js + Express yang menangani data buku dan proses
peminjaman. Service ini tidak boleh menyimpan password; untuk mengecek siapa yang login, panggil
auth-service lewat HTTP. Terapkan seluruh aturan bisnis RB-02 s.d. RB-06.
```

### Prompt 6 — Integrasi Frontend
```
Ubah script.js supaya memanggil auth-service dan library-service lewat fetch(), bukan lagi
localStorage untuk data buku/peminjaman. Pertahankan tampilan (style.css, struktur halaman) agar
tetap sama dengan versi sebelumnya.
```

### Prompt 7 — Uji Coba
```
Jalankan kedua service, lalu uji alur: login, lihat daftar buku, pinjam buku, coba pinjam buku
yang sama lagi (harus ditolak), coba pinjam buku ke-4 (harus ditolak karena maksimal 3), dan
reset sistem. Tunjukkan hasil masing-masing.
```

### Prompt 8 — Dokumentasi
```
Buatkan README, diagram arsitektur sebelum/sesudah (mermaid), dan dokumentasi penggunaan AI
Coding Tool beserta prompt yang dipakai, docker-compose untuk menjalankan semuanya, dan siapkan
proyek ini untuk di-push ke repository GitHub publik.
```

## 4. Masalah / Kesalahan yang Ditemukan dari Hasil AI (dan Perbaikannya)

Sesuai ketentuan poin 9 (kode AI tidak langsung dipakai tanpa diperiksa), berikut hal-hal yang
ditemukan saat meninjau dan menguji kode hasil AI, beserta perbaikannya:

| No | Masalah yang Ditemukan | Perbaikan |
|---|---|---|
| 1 | Draf awal `library-service` sempat dirancang untuk memvalidasi JWT sendiri menggunakan secret yang dibagi (*shared secret*). Ini melanggar prinsip pemisahan tanggung jawab microservice karena logika otentikasi jadi terduplikasi di dua tempat. | Diubah agar `library-service` memanggil endpoint `GET /api/auth/verify` milik `auth-service` lewat HTTP setiap kali butuh memastikan identitas pengguna — sesuai ketentuan #8 (antar service harus berkomunikasi lewat API) dan menjaga satu sumber kebenaran untuk otentikasi. |
| 2 | Perlu dipastikan password mahasiswa tidak disimpan sebagai teks biasa seperti pada versi lama (`localStorage` menyimpan data mahasiswa termasuk kredensial secara implisit di kode JS). | `auth-service` di-desain agar hanya menyimpan `passwordHash` (bcrypt), tidak pernah menyimpan atau mengirim password asli setelah proses login selesai. |
| 3 | Endpoint `POST /api/loans` awalnya perlu dipastikan menolak `bookId` yang tidak valid (bukan angka, atau buku tidak ada) — jika tidak diperiksa, service bisa crash atau menyimpan data yang salah. | Ditambahkan validasi eksplisit: `bookId` harus berupa angka valid dan bukunya harus ada di `books.json`, dengan respons error 400/404 yang jelas, diuji langsung dengan `curl`. |
| 4 | Saat `library-service` tidak bisa menghubungi `auth-service` (mis. service belum jalan), permintaan yang butuh login berisiko menggantung atau error yang membingungkan pengguna. | Ditambahkan `try/catch` pada middleware `requireAuth` yang mengembalikan respons `503` dengan pesan jelas ("Layanan autentikasi sedang tidak dapat diakses"), lalu diuji dengan mematikan `auth-service`. |
| 5 | Struktur data peminjaman awal berisiko duplikasi buku dipinjam dua kali secara bersamaan tanpa pemeriksaan atomik (dua request nyaris bersamaan). | Untuk skala praktikum ini file JSON dianggap cukup, namun hal ini didokumentasikan sebagai **batasan yang diketahui** (lihat bagian Batasan) — solusi produksinya adalah memakai database dengan transaksi/`unique constraint`, bukan file JSON. |

## 5. Hasil Pengujian (Regression Testing Singkat)

Diuji langsung lewat `curl` terhadap kedua service yang berjalan:

| Skenario | Hasil |
|---|---|
| Login dengan password salah | Ditolak, `401 { "error": "NIM atau Password salah." }` |
| Login dengan NIM & password benar | Berhasil, token JWT diterbitkan |
| `GET /api/books` tanpa login | Berhasil (publik), semua buku "tersedia" |
| `GET /api/loans` tanpa token | Ditolak, `401 Unauthorized` |
| Pinjam buku yang tersedia | Berhasil, status buku berubah jadi "dipinjam" |
| Pinjam buku yang sama dua kali | Ditolak, `409 { "error": "Buku sedang dipinjam." }` |
| Pinjam buku ke-4 setelah punya 3 aktif | Ditolak, `409 { "error": "Maksimal peminjaman adalah 3 buku aktif." }` |
| Reset sistem | Berhasil, seluruh buku kembali "tersedia" |

## 6. Batasan yang Diketahui (Known Limitations)

- Penyimpanan berbasis file JSON tidak aman untuk penulisan bersamaan (*concurrent write*) dalam
  skala produksi — cocok untuk prototype/praktikum, tidak untuk produksi.
- Tidak ada API Gateway terpisah; frontend memanggil kedua service secara langsung. Untuk sistem
  yang lebih besar, sebuah API Gateway dapat ditambahkan sebagai pintu masuk tunggal.
- Tidak ada fitur pengembalian buku otomatis setelah 7 hari (sesuai catatan requirement awal
  bahwa ini masih ambigu dan di luar cakupan requirement yang diberikan).
