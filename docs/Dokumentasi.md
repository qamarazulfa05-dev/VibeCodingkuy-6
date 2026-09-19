# Dokumentasi Penggunaan AI Coding Tool — Claude

## 1. Identitas Pengembangan

**Proyek:** Sistem Peminjaman Buku Perpustakaan — Microservice Edition  
**AI Coding Tool:** Claude (Anthropic)  
**Pendekatan:** AI-assisted / agentic coding  
**Basis pengembangan:** Proyek Praktikum 2 — Requirement Engineering dengan AI  
**Arsitektur akhir:** Frontend + `auth-service` + `library-service`

Dokumentasi ini menjelaskan bagaimana **Claude** digunakan sebagai AI Coding Tool dalam proses
pengembangan proyek, mulai dari memahami proyek awal, mempertahankan requirement, merancang
arsitektur microservice, menghasilkan kode, melakukan pemeriksaan, melakukan pengujian, sampai
menyusun dokumentasi.

> **Catatan:** User Story dan Acceptance Criteria dari proyek sebelumnya dipertahankan. Perubahan
> utama berada pada arsitektur, pemisahan tanggung jawab service, mekanisme autentikasi,
> komunikasi antar-service, dan penyimpanan data.

---

## 2. Tujuan Penggunaan AI Coding Tool

Claude digunakan untuk membantu tim dalam:

1. memahami kembali kode dan requirement proyek sebelumnya;
2. mengidentifikasi bagian yang perlu dipisahkan menjadi service;
3. merancang arsitektur microservice dengan tanggung jawab yang jelas;
4. membuat backend `auth-service` dan `library-service`;
5. mengintegrasikan frontend dengan REST API;
6. mempertahankan aturan bisnis yang telah ditetapkan;
7. menemukan dan memperbaiki potensi kesalahan implementasi;
8. membantu menyusun pengujian terhadap alur utama aplikasi; dan
9. membuat dokumentasi teknis proyek.

AI **tidak diposisikan sebagai pengganti pemeriksaan manusia**. Hasil kode perlu dibaca,
disesuaikan dengan requirement, dan diuji sebelum dianggap benar.

---

## 3. Kondisi Proyek Sebelum Dikembangkan

Versi sebelumnya merupakan prototype frontend dengan:

- HTML5;
- CSS3;
- Vanilla JavaScript;
- data dan state aplikasi yang terutama dikelola di sisi browser;
- autentikasi yang belum dipisahkan sebagai service; dan
- belum terdapat komunikasi antar-microservice.

Requirement utama yang harus tetap dipenuhi adalah:

- mahasiswa melakukan login;
- mahasiswa dapat melihat daftar buku;
- status buku dapat dilihat;
- mahasiswa dapat meminjam buku yang tersedia;
- maksimal 3 buku aktif per mahasiswa;
- buku yang sedang dipinjam tidak dapat dipinjam mahasiswa lain; dan
- periode peminjaman adalah 7 hari.

---

# 4. Alur Penggunaan Claude

Proses pengembangan dilakukan secara bertahap. Setiap prompt memiliki tujuan tertentu dan
hasilnya diperiksa terhadap requirement.

### Tahap 1 — Memahami proyek lama

Claude terlebih dahulu diarahkan untuk membaca struktur dan isi proyek lama. Tujuannya agar
pengembangan tidak dimulai dari nol dan agar requirement lama tidak hilang.

### Tahap 2 — Menentukan pembagian service

Claude diminta menentukan batas tanggung jawab microservice. Hasil akhirnya:

| Komponen | Tanggung jawab |
|---|---|
| `auth-service` | Login mahasiswa, hashing password, penerbitan JWT, dan verifikasi JWT |
| `library-service` | Buku, status ketersediaan, peminjaman, dan aturan bisnis perpustakaan |
| `frontend` | Antarmuka pengguna dan komunikasi ke REST API |

### Tahap 3 — Implementasi backend

Claude membantu membuat dua backend Node.js + Express yang berdiri sebagai service terpisah.
`library-service` tidak menyimpan password dan tidak melakukan verifikasi JWT secara mandiri.
Untuk request yang memerlukan autentikasi, service tersebut memanggil:

`GET /api/auth/verify`

pada `auth-service`.

Dengan demikian, proses autentikasi tetap terpusat pada `auth-service`, sementara aturan
peminjaman tetap berada pada `library-service`.

### Tahap 4 — Integrasi frontend

Frontend diubah agar menggunakan `fetch()` untuk berkomunikasi dengan service:

- `POST /api/auth/login` → login;
- `GET /api/books` → daftar buku;
- `GET /api/loans` → informasi peminjaman;
- `POST /api/loans` → peminjaman;
- `POST /api/system/reset` → reset data peminjaman.

### Tahap 5 — Pemeriksaan dan pengujian

Hasil implementasi diperiksa dengan fokus pada:

- validasi login;
- token JWT;
- komunikasi `library-service` → `auth-service`;
- buku tersedia/tidak tersedia;
- batas maksimal 3 buku;
- penolakan peminjaman buku yang sudah dipinjam;
- periode peminjaman 7 hari;
- error handling; dan
- reset sistem.

---

# 5. Prompt Utama yang Digunakan

## Prompt 1 — Instruksi Pengembangan Utama

```text
Kembangkan proyek kelompok sebelumnya menjadi aplikasi berbasis microservice dengan
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

Lampirkan proyek lama dan dokumen requirement sebagai konteks.
```

**Tujuan:** memberikan konteks tugas dan batasan utama kepada Claude.

**Output yang diharapkan:** rancangan pengembangan dari aplikasi monolitik/frontend-only menuju
arsitektur microservice tanpa menghilangkan requirement lama.

---

## Prompt 2 — Analisis Proyek dan Requirement

```text
Baca ulang index.html, style.css, dan script.js dari proyek lama, serta User Story dan
Acceptance Criteria pada dokumen Praktikum 2.

Identifikasi:
1. fitur yang sudah tersedia;
2. data yang digunakan;
3. alur login;
4. alur peminjaman;
5. seluruh aturan bisnis;
6. bagian yang harus dipertahankan; dan
7. bagian yang perlu diubah untuk arsitektur microservice.

Jangan mengubah requirement. Buat ringkasan sebelum merancang arsitektur baru.
```

**Tujuan:** memastikan Claude memahami sistem sebelum menulis kode baru.

**Hasil pada proyek:** requirement kemudian didokumentasikan kembali pada
`docs/USER_STORY_DAN_AC.md`.

---

## Prompt 3 — Perancangan Arsitektur Microservice

```text
Pecah aplikasi ini menjadi minimal 2 microservice yang masing-masing memiliki tanggung jawab
jelas dan berkomunikasi melalui REST API.

Gunakan pembagian yang masuk akal untuk sistem perpustakaan:
- service autentikasi;
- service buku dan peminjaman.

Pastikan:
- password tidak dikelola oleh library-service;
- data tiap service memiliki ownership yang jelas;
- library-service dapat mengetahui identitas pengguna melalui API auth-service;
- aturan maksimal 3 buku, buku tidak boleh dipinjam dua kali, dan periode 7 hari tetap berlaku.

Jelaskan alur komunikasi antar-service dan endpoint yang diperlukan.
```

**Hasil:** dua service utama dibuat:

- `services/auth-service`
- `services/library-service`

dan frontend tetap menjadi client aplikasi.

---

# 6. Prompt Implementasi `auth-service`

```text
Buat auth-service menggunakan Node.js dan Express.

Tanggung jawab:
1. login mahasiswa menggunakan NIM dan password;
2. validasi NIM dan password;
3. membaca data mahasiswa dari students.json;
4. membandingkan password menggunakan bcrypt;
5. menerbitkan JWT setelah login berhasil;
6. menyediakan endpoint untuk verifikasi token;
7. menyediakan health check.

Gunakan:
- Express;
- bcryptjs;
- jsonwebtoken;
- cors.

Aturan validasi:
- NIM wajib diisi;
- NIM hanya boleh berupa angka;
- NIM minimal 5 digit;
- password minimal 6 karakter.

Jangan masukkan logika buku atau peminjaman ke auth-service.
```

### Implementasi yang dihasilkan

File utama:

`services/auth-service/server.js`

Endpoint:

| Method | Endpoint | Fungsi |
|---|---|---|
| POST | `/api/auth/login` | Validasi kredensial dan menerbitkan JWT |
| GET | `/api/auth/verify` | Memverifikasi JWT dan mengembalikan identitas |
| GET | `/api/health` | Health check |

Password mahasiswa disimpan sebagai `passwordHash`, bukan password plaintext.

---

# 7. Prompt Implementasi `library-service`

```text
Buat library-service menggunakan Node.js dan Express.

Tanggung jawab:
1. membaca daftar buku;
2. menampilkan status buku;
3. mencatat peminjaman;
4. menampilkan peminjaman mahasiswa yang sedang login;
5. menerapkan aturan bisnis peminjaman;
6. menyediakan reset data peminjaman.

Library-service tidak boleh menyimpan password dan tidak boleh melakukan autentikasi
secara terpisah dari auth-service.

Untuk request yang membutuhkan login:
- ambil Bearer token dari request;
- panggil auth-service melalui HTTP GET /api/auth/verify;
- jika token valid, gunakan NIM hasil verifikasi untuk menjalankan aturan peminjaman.

Terapkan:
- RB-01 login wajib;
- RB-02 hanya buku tersedia yang boleh dipinjam;
- RB-03 maksimal 3 buku aktif;
- RB-04 satu buku tidak boleh dipinjam dua kali;
- RB-05 masa peminjaman 7 hari;
- RB-06 buku yang dipinjam berstatus dipinjam.
```

### Implementasi yang dihasilkan

File utama:

`services/library-service/server.js`

Endpoint:

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/api/books` | Menampilkan buku dan status |
| GET | `/api/loans` | Menampilkan peminjaman mahasiswa |
| POST | `/api/loans` | Memproses peminjaman |
| POST | `/api/system/reset` | Menghapus seluruh data peminjaman |
| GET | `/api/health` | Health check |

Pengecekan maksimal 3 buku dilakukan dengan logika:

```text
jumlah buku aktif mahasiswa >= 3
→ tolak peminjaman
```

---

# 8. Prompt Integrasi Frontend

```text
Integrasikan frontend lama dengan kedua microservice.

Pertahankan struktur HTML dan styling yang sudah ada.

Ubah JavaScript agar:
1. login menggunakan POST /api/auth/login;
2. menyimpan token sesi di browser;
3. mengirim Bearer token untuk endpoint yang membutuhkan autentikasi;
4. mengambil daftar buku dari library-service;
5. mengambil informasi peminjaman dari library-service;
6. melakukan peminjaman melalui POST /api/loans;
7. menangani HTTP 401 dengan menghapus sesi dan kembali ke halaman login;
8. menampilkan error service dengan jelas.

Jangan menyimpan data buku dan peminjaman sebagai sumber data utama di localStorage.
```

### File yang terkait

- `frontend/index.html`
- `frontend/style.css`
- `frontend/script.js`
- `frontend/config.js`

`config.js` menyimpan alamat:

```text
AUTH_SERVICE_URL = http://localhost:4001
LIBRARY_SERVICE_URL = http://localhost:4002
```

---

# 9. Prompt Pengujian

```text
Jalankan aplikasi dan lakukan pengujian terhadap alur utama.

Uji minimal:
1. login dengan password salah;
2. login dengan kredensial benar;
3. melihat daftar buku;
4. mengakses peminjaman tanpa token;
5. meminjam buku tersedia;
6. mencoba meminjam buku yang sama;
7. meminjam sampai 3 buku;
8. mencoba meminjam buku ke-4;
9. memeriksa tanggal pengembalian 7 hari;
10. melakukan reset sistem.

Untuk setiap skenario, catat request, hasil yang diharapkan, hasil aktual, dan status
berhasil/gagal.
```

---

# 10. Prompt Review dan Perbaikan Kode

```text
Review seluruh kode hasil implementasi terhadap requirement dan prinsip microservice.

Cari:
- pelanggaran separation of responsibility;
- validasi yang kurang;
- error handling;
- ketidaksesuaian acceptance criteria;
- duplikasi logika autentikasi;
- kemungkinan data tidak konsisten;
- masalah komunikasi antar-service.

Jangan hanya menjelaskan masalah. Tunjukkan file dan bagian yang perlu diperbaiki,
lalu berikan perubahan kode yang diperlukan.
```

---

# 11. Hasil Review yang Perlu Diperhatikan

| Temuan | Tindakan/perbaikan |
|---|---|
| Verifikasi autentikasi sebaiknya tidak diduplikasi di library-service | `library-service` memanggil `/api/auth/verify` pada `auth-service` |
| Password tidak boleh menjadi data yang dikirim/disimpan sembarangan | `auth-service` menggunakan `bcryptjs` dan hanya menyimpan hash |
| `bookId` perlu divalidasi | `POST /api/loans` melakukan validasi angka dan keberadaan buku |
| Auth-service dapat tidak tersedia | `requireAuth` menangani kegagalan komunikasi dan mengembalikan HTTP 503 |
| Batas maksimal peminjaman harus dipastikan di backend | `library-service` memeriksa jumlah buku aktif sebelum menyimpan loan |
| Penyimpanan file JSON memiliki keterbatasan concurrency | Dicatat sebagai limitation; produksi sebaiknya menggunakan database dengan transaksi/constraint |

---

# 12. Pemetaan Requirement ke Implementasi

| Requirement | Implementasi |
|---|---|
| Login mahasiswa | `auth-service` → `POST /api/auth/login` |
| Token autentikasi | JWT |
| Password aman | `bcryptjs` |
| Verifikasi identitas | `auth-service` → `/api/auth/verify` |
| Daftar buku | `library-service` → `GET /api/books` |
| Status tersedia/dipinjam | Dibentuk berdasarkan data `loans.json` |
| Maksimal 3 buku | `MAX_BUKU_AKTIF = 3` |
| Buku tidak boleh dipinjam dua kali | Pemeriksaan `peminjaman[idBuku]` |
| Lama pinjam 7 hari | `LAMA_PEMINJAMAN_HARI = 7` |
| Informasi peminjaman | `GET /api/loans` + frontend |
| Komunikasi antar-service | HTTP REST API |
| Containerization | Docker + `docker-compose.yml` |

---

# 13. Struktur Proyek Hasil Pengembangan

```text
perpustakaan-microservices/
│
├── frontend/
│   ├── index.html
│   ├── style.css
│   ├── script.js
│   ├── config.js
│   └── Dockerfile
│
├── services/
│   ├── auth-service/
│   │   ├── data/
│   │   │   └── students.json
│   │   ├── server.js
│   │   ├── package.json
│   │   ├── package-lock.json
│   │   ├── .env.example
│   │   └── Dockerfile
│   │
│   └── library-service/
│       ├── data/
│       │   ├── books.json
│       │   └── loans.json
│       ├── server.js
│       ├── package.json
│       ├── package-lock.json
│       ├── .env.example
│       └── Dockerfile
│
├── docs/
│   ├── AI_USAGE.md
│   ├── ARCHITECTURE.md
│   └── USER_STORY_DAN_AC.md
│
├── docker-compose.yml
├── README.md
└── .gitignore
```

---

# 14. Alur Fitur Utama: Peminjaman Buku

```text
Mahasiswa
    │
    ▼
Frontend
    │
    │ POST /api/auth/login
    ▼
auth-service
    │
    │ JWT
    ▼
Frontend
    │
    │ POST /api/loans
    │ Authorization: Bearer <JWT>
    ▼
library-service
    │
    │ GET /api/auth/verify
    ▼
auth-service
    │
    │ valid + NIM
    ▼
library-service
    │
    ├── cek buku tersedia
    ├── cek maksimal 3 buku
    ├── tentukan tanggal kembali +7 hari
    └── simpan loan
    │
    ▼
Frontend
    │
    ▼
Informasi peminjaman ditampilkan
```

Alur ini menunjukkan bahwa satu fitur utama benar-benar melibatkan lebih dari satu service.

---

# 15. Pengujian yang Harus Dibuktikan

| No | Skenario | Expected Result |
|---|---|---|
| 1 | NIM kosong | Login ditolak |
| 2 | NIM mengandung huruf | Login ditolak |
| 3 | NIM kurang dari 5 digit | Login ditolak |
| 4 | Password kurang dari 6 karakter | Login ditolak |
| 5 | Password salah | HTTP 401 |
| 6 | Login benar | JWT diterbitkan |
| 7 | Akses endpoint protected tanpa token | HTTP 401 |
| 8 | Pinjam buku tersedia | HTTP 201 |
| 9 | Pinjam buku yang sedang dipinjam | HTTP 409 |
| 10 | Sudah memiliki 3 buku aktif lalu pinjam lagi | HTTP 409 |
| 11 | Service autentikasi tidak tersedia | HTTP 503 pada request protected |
| 12 | Reset sistem | Semua peminjaman dihapus dan buku kembali tersedia |

> Untuk laporan praktikum, bukti pengujian sebaiknya dilengkapi screenshot terminal/browser atau
> hasil request API yang benar-benar dijalankan oleh kelompok.

---

# 16. Peran Manusia dalam Penggunaan AI

Walaupun Claude digunakan untuk menghasilkan dan mengubah kode, keputusan akhir tetap berada
pada tim. Pemeriksaan manusia diperlukan untuk:

1. memastikan hasil sesuai requirement;
2. memastikan pembagian tanggung jawab service masuk akal;
3. memeriksa keamanan dasar autentikasi;
4. menguji skenario normal dan error;
5. memeriksa apakah Acceptance Criteria tetap terpenuhi;
6. mengidentifikasi keterbatasan prototype; dan
7. memastikan kode yang dihasilkan AI tidak diterima secara otomatis tanpa review.

Dengan pendekatan tersebut, AI digunakan sebagai **coding assistant**, bukan sebagai satu-satunya
pihak yang menentukan desain dan kebenaran sistem.

---

# 17. Keterbatasan Implementasi

### 17.1 Penyimpanan JSON

Data masih disimpan dalam file JSON:

- `students.json`
- `books.json`
- `loans.json`

Pendekatan ini ringan dan cocok untuk prototype/praktikum, tetapi tidak ideal untuk produksi,
khususnya ketika banyak request melakukan penulisan bersamaan.

### 17.2 Belum ada API Gateway

Frontend saat ini berkomunikasi langsung dengan kedua service. Pada sistem yang lebih besar,
API Gateway dapat digunakan sebagai satu pintu masuk.

### 17.3 Pengembalian buku

Requirement menetapkan periode 7 hari, tetapi prototype belum menyediakan workflow pengembalian
buku secara otomatis maupun endpoint return-book. Implementasi tersebut dapat menjadi
pengembangan berikutnya.

### 17.4 Keamanan produksi

JWT secret pada konfigurasi contoh harus diganti dengan secret yang aman melalui environment
variable saat deployment. Sistem produksi juga memerlukan hardening tambahan, misalnya rate
limiting, HTTPS, secret management, database transaction, dan logging yang lebih matang.

---

# 18. Kesimpulan

Penggunaan Claude dalam proyek ini membantu mempercepat proses transformasi prototype
perpustakaan menjadi aplikasi dengan arsitektur microservice. Proses dilakukan secara bertahap:
**memahami requirement → merancang service → menghasilkan kode → mengintegrasikan frontend →
review → testing → dokumentasi**.

Hasil akhirnya mempertahankan requirement utama sistem perpustakaan sekaligus memperkenalkan:

- `auth-service` untuk autentikasi;
- `library-service` untuk buku dan peminjaman;
- REST API untuk komunikasi antar-service;
- JWT untuk sesi autentikasi;
- bcrypt untuk password hashing;
- Docker Compose untuk menjalankan komponen secara bersama-sama.

Hal terpenting dari penggunaan AI Coding Tool adalah bahwa **kode hasil AI tetap harus diperiksa
dan diuji terhadap requirement**. Dengan demikian, penggunaan Claude menjadi bagian dari proses
rekayasa perangkat lunak, bukan sekadar aktivitas menghasilkan source code.
