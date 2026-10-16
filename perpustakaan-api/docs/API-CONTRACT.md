# API Contract: Perpustakaan API

Versi 1.0 · Satu backend service (Laravel 13 + Sanctum + MySQL) · HTTP/JSON

## Informasi Umum

| Item | Nilai |
|---|---|
| Base URL | `http://localhost:8000` (semua endpoint berawalan `/api`) |
| Format | Request dan response `application/json` |
| Header wajib semua request | `Accept: application/json` |
| Header tambahan (endpoint 🔒) | `Authorization: Bearer <token>` |
| Header tambahan (request ber-body) | `Content-Type: application/json` |
| Masa berlaku token | 2 jam sejak login |
| Format waktu | ISO 8601 UTC, contoh `2026-10-15T08:30:00Z` |

### Daftar Endpoint

| Layanan | Method | Endpoint | Fungsi | Login? |
|---|---|---|---|---|
| Sistem | GET | `/api/health` | Cek API dan koneksi database | Tidak |
| Auth | POST | `/api/auth/login` | Login, menerbitkan token | Tidak |
| Auth | GET | `/api/auth/verify` | Cek token dan identitas pemilik | Ya 🔒 |
| Auth | POST | `/api/auth/logout` | Cabut token yang sedang dipakai | Ya 🔒 |
| Books | GET | `/api/books` | Daftar buku + status | Tidak |
| Books | GET | `/api/books/{id}` | Detail satu buku | Tidak |
| Borrowings | POST | `/api/borrowings` | Meminjam buku | Ya 🔒 |
| Borrowings | GET | `/api/borrowings` | Riwayat dan peminjaman aktif milik sendiri | Ya 🔒 |
| Borrowings | PUT/PATCH | `/api/borrowings/{id}/return` | Mengembalikan buku | Ya 🔒 |

### Format Error Seragam

Semua error berbentuk:

```json
{ "error": "pesan error" }
```

### Ringkasan Status Code

| Kode | Arti dalam API ini |
|---|---|
| 200 | Berhasil |
| 201 | Peminjaman berhasil dibuat |
| 400 | Input tidak valid (validasi) |
| 401 | Belum login / token salah / token kedaluwarsa / kredensial salah |
| 403 | Mengembalikan peminjaman milik mahasiswa lain |
| 404 | Data atau endpoint tidak ditemukan |
| 409 | Melanggar business rule (buku tidak tersedia, batas 3 buku, sudah dikembalikan) |
| 429 | Terlalu banyak percobaan login (maks. 20 per menit) |

### Business Rule

| ID | Aturan |
|---|---|
| BR-01 | Satu mahasiswa maksimal 3 buku aktif (dipinjam dan belum dikembalikan). |
| BR-02 | Buku yang tidak tersedia (sedang dipinjam siapa pun) tidak dapat dipinjam. |
| BR-03 | Jatuh tempo = tanggal pinjam + 7 hari. |

---

## 1. GET `/api/health`

**Tujuan:** memastikan API hidup dan database terhubung. Tanpa parameter, tanpa header khusus.

**200 OK**
```json
{ "service": "perpustakaan-api", "status": "ok", "database": "ok", "time": "2026-10-15T08:30:00Z" }
```
`database` bernilai `"down"` jika koneksi database gagal (status HTTP tetap 200).

---

## 2. POST `/api/auth/login`

**Tujuan:** login dengan akun yang tersimpan di database (FR-01). Tidak butuh token.

**Request body**

| Field | Tipe | Wajib | Validasi |
|---|---|---|---|
| `nim` | string (angka juga diterima) | Ya | Hanya angka, minimal 5 digit |
| `password` | string | Ya | Minimal 6 karakter |

```json
{ "nim": "230001", "password": "123456" }
```

**200 OK**
```json
{ "token": "1|AbCdEf...", "nim": "230001", "nama": "Mahasiswa Satu", "expiresIn": "2h" }
```

**Kasus gagal**

| Kondisi | Status | Response |
|---|---|---|
| `nim` kosong / tidak dikirim | 400 | `{"error":"NIM wajib diisi."}` |
| `nim` mengandung non-angka | 400 | `{"error":"NIM hanya boleh berisi angka."}` |
| `nim` kurang dari 5 digit | 400 | `{"error":"NIM harus terdiri dari minimal 5 angka."}` |
| `password` kosong / kurang dari 6 karakter | 400 | `{"error":"Password harus terdiri dari minimal 6 karakter."}` |
| NIM tidak terdaftar atau password salah | 401 | `{"error":"NIM atau Password salah."}` |
| Lebih dari 20 percobaan per menit | 429 | pesan bawaan Laravel |

---

## 3. GET `/api/auth/verify` 🔒

**Tujuan:** mengecek token masih valid dan mengetahui pemiliknya.
**Header:** `Authorization: Bearer <token>`

**200 OK**
```json
{ "valid": true, "nim": "230001", "nama": "Mahasiswa Satu" }
```
**401** tanpa/dengan token salah atau kedaluwarsa: `{"error":"Silakan login terlebih dahulu."}`

---

## 4. POST `/api/auth/logout` 🔒

**Tujuan:** mencabut token yang sedang dipakai. Token tidak bisa dipakai lagi setelahnya.
**Header:** `Authorization: Bearer <token>`

**200 OK** `{ "message": "Logout berhasil." }`
**401** jika token tidak valid.

---

## 5. GET `/api/books`

**Tujuan:** daftar seluruh buku beserta status ketersediaan (FR-02). Publik, tanpa parameter.

**200 OK**
```json
[
  { "id": 1, "judul": "Laskar Pelangi", "penulis": "Andrea Hirata", "status": "tersedia" },
  { "id": 2, "judul": "Bumi Manusia", "penulis": "Pramoedya Ananta Toer", "status": "dipinjam" }
]
```
`status` bernilai `tersedia` atau `dipinjam`, dihitung dari ada tidaknya peminjaman aktif untuk buku itu.

---

## 6. GET `/api/books/{id}`

**Tujuan:** detail satu buku (FR-02). Publik.
**Path parameter:** `id` (angka) — ID buku.

**200 OK**
```json
{ "id": 1, "judul": "Laskar Pelangi", "penulis": "Andrea Hirata", "status": "tersedia" }
```

| Kondisi | Status | Response |
|---|---|---|
| ID tidak ada di database | 404 | `{"error":"Data buku tidak ditemukan."}` |
| ID bukan angka (`/api/books/abc`) | 404 | `{"error":"Endpoint tidak ditemukan."}` |

---

## 7. POST `/api/borrowings` 🔒

**Tujuan:** mahasiswa yang login meminjam satu buku (FR-03). Mencatat tanggal pinjam, menetapkan jatuh tempo +7 hari, dan membuat buku berstatus `dipinjam`.
**Header:** `Authorization`, `Content-Type: application/json`

**Request body**

| Field | Tipe | Wajib | Validasi |
|---|---|---|---|
| `bookId` | integer | Ya | Angka > 0 dan buku harus ada |

```json
{ "bookId": 1 }
```

**201 Created**
```json
{
  "message": "Buku berhasil dipinjam.",
  "borrowing": {
    "id": 12,
    "idBuku": 1,
    "judulBuku": "Laskar Pelangi",
    "penulisBuku": "Andrea Hirata",
    "nimMahasiswa": "230001",
    "status": "dipinjam",
    "tanggalPinjam": "2026-10-15T08:30:00Z",
    "tanggalJatuhTempo": "2026-10-22T08:30:00Z",
    "tanggalDikembalikan": null
  }
}
```

**Kasus gagal**

| Kondisi | Status | Response |
|---|---|---|
| Tanpa token / token salah | 401 | `{"error":"Silakan login terlebih dahulu."}` |
| `bookId` kosong, bukan angka, atau ≤ 0 | 400 | `{"error":"bookId tidak valid."}` |
| Buku tidak ada | 404 | `{"error":"Data buku tidak ditemukan."}` |
| Buku sedang dipinjam (siapa pun) — BR-02 | 409 | `{"error":"Buku sedang dipinjam."}` |
| Mahasiswa sudah punya 3 buku aktif — BR-01 | 409 | `{"error":"Maksimal peminjaman adalah 3 buku aktif."}` |

Urutan pengecekan: token → validasi `bookId` → buku ada → buku tersedia (BR-02) → batas 3 buku (BR-01).

---

## 8. GET `/api/borrowings` 🔒

**Tujuan:** melihat riwayat dan peminjaman aktif **milik mahasiswa yang login saja**.
**Query parameter (opsional):** `status` = `dipinjam` | `dikembalikan` untuk memfilter. Tanpa parameter, semua ditampilkan (urut tanggal pinjam).

**200 OK** — `GET /api/borrowings`
```json
{
  "nim": "230001",
  "jumlahAktif": 1,
  "maksimal": 3,
  "borrowings": [
    {
      "id": 11, "idBuku": 2, "judulBuku": "Bumi Manusia", "penulisBuku": "Pramoedya Ananta Toer",
      "nimMahasiswa": "230001", "status": "dikembalikan",
      "tanggalPinjam": "2026-10-10T03:00:00Z", "tanggalJatuhTempo": "2026-10-17T03:00:00Z",
      "tanggalDikembalikan": "2026-10-12T05:20:00Z"
    },
    {
      "id": 12, "idBuku": 1, "judulBuku": "Laskar Pelangi", "penulisBuku": "Andrea Hirata",
      "nimMahasiswa": "230001", "status": "dipinjam",
      "tanggalPinjam": "2026-10-15T08:30:00Z", "tanggalJatuhTempo": "2026-10-22T08:30:00Z",
      "tanggalDikembalikan": null
    }
  ]
}
```
`jumlahAktif` selalu menghitung peminjaman berstatus `dipinjam` saja, apa pun filter yang dipakai.

| Kondisi | Status | Response |
|---|---|---|
| Tanpa token | 401 | `{"error":"Silakan login terlebih dahulu."}` |
| `status` selain dua nilai di atas | 400 | `{"error":"Parameter status harus 'dipinjam' atau 'dikembalikan'."}` |

---

## 9. PUT/PATCH `/api/borrowings/{id}/return` 🔒

**Tujuan:** mengembalikan buku (FR-04). Status peminjaman menjadi `dikembalikan`, `tanggalDikembalikan` diisi, dan buku kembali `tersedia`. Kuota 3 buku mahasiswa ikut bebas.
**Path parameter:** `id` (angka) — ID peminjaman (field `borrowing.id`, **bukan** ID buku).
**Header:** `Authorization`. Tanpa request body.

**200 OK**
```json
{
  "message": "Buku berhasil dikembalikan.",
  "borrowing": {
    "id": 12, "idBuku": 1, "judulBuku": "Laskar Pelangi", "penulisBuku": "Andrea Hirata",
    "nimMahasiswa": "230001", "status": "dikembalikan",
    "tanggalPinjam": "2026-10-15T08:30:00Z", "tanggalJatuhTempo": "2026-10-22T08:30:00Z",
    "tanggalDikembalikan": "2026-10-16T01:10:00Z"
  }
}
```

**Kasus gagal**

| Kondisi | Status | Response |
|---|---|---|
| Tanpa token / token salah | 401 | `{"error":"Silakan login terlebih dahulu."}` |
| ID peminjaman tidak ada | 404 | `{"error":"Data peminjaman tidak ditemukan."}` |
| Peminjaman milik mahasiswa lain | 403 | `{"error":"Peminjaman ini bukan milik Anda."}` |
| Peminjaman sudah dikembalikan sebelumnya | 409 | `{"error":"Buku sudah dikembalikan."}` |

---

## Contoh Alur Lengkap (cURL)

```bash
# 1. Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"nim":"230001","password":"123456"}'

# 2. Pinjam buku 1 (ganti TOKEN)
curl -X POST http://localhost:8000/api/borrowings \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -H "Authorization: Bearer TOKEN" -d '{"bookId":1}'

# 3. Cek status buku
curl http://localhost:8000/api/books/1 -H "Accept: application/json"

# 4. Kembalikan (ganti 1 dengan borrowing.id dari langkah 2)
curl -X PATCH http://localhost:8000/api/borrowings/1/return \
  -H "Accept: application/json" -H "Authorization: Bearer TOKEN"
```
