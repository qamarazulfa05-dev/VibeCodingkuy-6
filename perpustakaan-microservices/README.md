# Sistem Peminjaman Buku Perpustakaan — Microservice Edition

Proyek ini adalah pengembangan lanjutan dari prototype **Sistem Peminjaman Buku Perpustakaan**
(Praktikum 2 – Requirement Engineering dengan AI, Kelompok 6) yang sebelumnya berupa aplikasi
frontend murni (HTML + CSS + Vanilla JS) dengan penyimpanan `localStorage`.

Pada pengembangan ini, aplikasi dipecah menjadi **2 microservice** yang saling berkomunikasi lewat
API, sehingga data login dan data peminjaman tidak lagi hanya tersimpan di browser, melainkan
dikelola oleh backend yang sesungguhnya.

> User Story dan Acceptance Criteria yang digunakan **tetap sama** dengan dokumen Praktikum 2
> (lihat [`docs/USER_STORY_DAN_AC.md`](docs/USER_STORY_DAN_AC.md)). Yang berubah hanya arsitektur
> dan cara penyimpanan datanya.

---

## 1. Anggota Kelompok 6

- Nor Dede Sanjaya (2441919051)
- Simbi Meylani Putri (2441919043)
- Qamara Zulfa (2441919036)
- Riya Monika (2441919049)
- Nita Abidah (2441919014)
- Nadila Syafanah (2441919021)

---

## 2. Arsitektur Singkat

```
┌────────────┐      login (NIM & password)      ┌───────────────┐
│  Frontend  │ ────────────────────────────────► │  auth-service │
│ (Static    │ ◄──────────────────────────────── │   (port 4001) │
│  HTML/JS)  │        JWT token                  └───────┬───────┘
│ (port 8080)│                                            │ verify token
│            │   lihat buku / pinjam buku (Bearer token)  │ (HTTP API)
│            │ ────────────────────────────────►┌─────────▼──────────┐
│            │ ◄──────────────────────────────── │  library-service   │
└────────────┘        daftar buku & peminjaman   │    (port 4002)     │
                                                  └─────────────────────┘
```

Penjelasan lengkap beserta diagram arsitektur "sebelum" dan "sesudah" ada di
[`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md).

---

## 3. Microservice yang Dibuat

| Service | Port | Tanggung Jawab |
|---|---|---|
| **auth-service** | 4001 | Login mahasiswa (NIM + password) dan penerbitan/verifikasi JWT token. Tidak tahu apa-apa soal buku. |
| **library-service** | 4002 | Data buku, status ketersediaan, dan proses peminjaman (termasuk seluruh aturan bisnis RB-01 s.d. RB-08). Tidak menyimpan password sama sekali; setiap request yang butuh login diverifikasi dengan memanggil `auth-service` lewat HTTP. |
| **frontend** | 8080 | Halaman statis (HTML/CSS/JS) yang memanggil kedua service di atas lewat `fetch()`. |

Setiap service memiliki **data miliknya sendiri** (`students.json` di auth-service,
`books.json` & `loans.json` di library-service) — tidak ada database yang dibagi antar service,
sesuai prinsip microservice.

---

## 4. Alur Fitur yang Melibatkan Kedua Service (Wajib Ketentuan #6)

Alur **"Mahasiswa meminjam buku"** melibatkan kedua service secara langsung:

1. Mahasiswa login di frontend → frontend memanggil `POST /api/auth/login` ke **auth-service** →
   auth-service mengembalikan JWT token.
2. Frontend memanggil `GET /api/books` ke **library-service** untuk menampilkan daftar buku
   (tidak perlu token, publik).
3. Mahasiswa klik "Pinjam" → frontend memanggil `POST /api/loans` ke **library-service** dengan
   header `Authorization: Bearer <token>`.
4. **library-service tidak memvalidasi token itu sendiri.** Ia memanggil
   `GET /api/auth/verify` milik **auth-service** lewat HTTP untuk memastikan token valid dan
   mendapatkan NIM pemiliknya. Inilah komunikasi antar-service yang diminta pada ketentuan tugas.
5. Setelah auth-service mengonfirmasi identitas, library-service menjalankan aturan bisnis
   (maksimal 3 buku aktif, buku tidak boleh dipinjam dua kali, masa pinjam 7 hari), menyimpan
   data peminjaman, lalu mengembalikan hasilnya ke frontend.

---

## 5. Teknologi yang Digunakan

| Kebutuhan | Sebelumnya (Praktikum 2) | Sekarang |
|---|---|---|
| Frontend | HTML5, CSS3, Vanilla JavaScript | HTML5, CSS3, Vanilla JavaScript (tetap, tapi memanggil REST API) |
| Backend | Tidak ada | Node.js 20 + Express (2 service terpisah) |
| Autentikasi | Dicek langsung di JS dengan data hardcode | JSON Web Token (JWT), password di-hash dengan `bcryptjs` |
| Komunikasi antar service | Tidak ada | REST API (`fetch` bawaan Node.js) |
| Penyimpanan data | `localStorage` di browser | File JSON per service (`students.json`, `books.json`, `loans.json`) — berperan sebagai "database" milik masing-masing service |
| Containerization | Tidak ada | Docker + Docker Compose |

> Catatan: penyimpanan berbasis file JSON dipakai agar setup tetap ringan untuk kebutuhan
> praktikum. Untuk produksi, `data/*.json` pada masing-masing service dapat diganti dengan
> database sungguhan (mis. PostgreSQL untuk auth-service, MongoDB/PostgreSQL untuk
> library-service) tanpa mengubah kontrak API antar service.

---

## 6. Cara Menjalankan

### A. Menggunakan Docker Compose (paling mudah)

```bash
docker compose up --build
```

- Frontend: http://localhost:8080
- auth-service: http://localhost:4001
- library-service: http://localhost:4002

### B. Menjalankan Manual (tanpa Docker)

Buka 3 terminal terpisah:

```bash
# Terminal 1 - auth-service
cd services/auth-service
npm install
cp .env.example .env
npm start
```

```bash
# Terminal 2 - library-service
cd services/library-service
npm install
cp .env.example .env
npm start
```

```bash
# Terminal 3 - frontend (server statis apa saja, contoh pakai Python)
cd frontend
python3 -m http.server 8080
```

Lalu buka `http://localhost:8080` di browser.

### Akun untuk Login (data contoh)

| NIM | Password |
|---|---|
| 230001 | 123456 |
| 230002 | 654321 |

---

## 7. Struktur Direktori

```
perpustakaan-microservices/
├── docker-compose.yml
├── README.md
├── docs/
│   ├── ARCHITECTURE.md         # diagram arsitektur sebelum & sesudah
│   ├── AI_USAGE.md             # dokumentasi penggunaan AI Coding Tool & prompt
│   └── USER_STORY_DAN_AC.md    # user story & acceptance criteria (dari Praktikum 2)
├── services/
│   ├── auth-service/
│   │   ├── server.js
│   │   ├── package.json
│   │   ├── Dockerfile
│   │   ├── .env.example
│   │   └── data/students.json
│   └── library-service/
│       ├── server.js
│       ├── package.json
│       ├── Dockerfile
│       ├── .env.example
│       └── data/books.json
└── frontend/
    ├── index.html
    ├── style.css
    ├── config.js
    ├── script.js
    └── Dockerfile
```

---

## 8. Dokumentasi Lengkap

- [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) — arsitektur sebelum vs sesudah, diagram service,
  daftar endpoint API.
- [`docs/AI_USAGE.md`](docs/AI_USAGE.md) — AI Coding Tool yang digunakan, bagaimana AI membantu,
  seluruh prompt yang dipakai, serta masalah/kesalahan hasil AI dan cara memperbaikinya.
- [`docs/USER_STORY_DAN_AC.md`](docs/USER_STORY_DAN_AC.md) — User Story & Acceptance Criteria asli
  dari Praktikum 2 yang tetap dipertahankan pada pengembangan ini.
