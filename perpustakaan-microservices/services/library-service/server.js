/**
 * ==================================================
 * LIBRARY SERVICE
 * ==================================================
 * Tanggung jawab service ini HANYA satu hal:
 * mengelola data buku dan proses peminjaman.
 *
 * Service ini TIDAK menyimpan password atau logika
 * login sama sekali. Untuk memastikan siapa yang
 * sedang request, service ini memanggil AUTH-SERVICE
 * lewat HTTP (GET /api/auth/verify) - inilah komunikasi
 * antar microservice yang diminta pada ketentuan tugas.
 *
 * Endpoint:
 *  GET  /api/books          -> daftar buku + status
 *  GET  /api/loans          -> daftar peminjaman aktif mahasiswa (butuh login)
 *  POST /api/loans          -> pinjam buku (butuh login)
 *  POST /api/system/reset   -> reset seluruh data peminjaman (fitur "Restart Sistem")
 *  GET  /api/health         -> health check
 * ==================================================
 */

const express = require("express");
const cors = require("cors");
const fs = require("fs");
const path = require("path");

const app = express();

app.use(cors());
app.use(express.json());

const PORT = process.env.PORT || 4002;
const AUTH_SERVICE_URL = process.env.AUTH_SERVICE_URL || "http://localhost:4001";

const BOOKS_FILE = path.join(__dirname, "data", "books.json");
const LOANS_FILE = path.join(__dirname, "data", "loans.json");

const MAX_BUKU_AKTIF = 3;
const LAMA_PEMINJAMAN_HARI = 7;

// ==================================================
// DATA BUKU (statis, dibaca dari file)
// ==================================================
function bacaBuku() {
    const raw = fs.readFileSync(BOOKS_FILE, "utf-8");
    return JSON.parse(raw);
}

// ==================================================
// DATA PEMINJAMAN (disimpan sebagai "database" file JSON
// milik service ini sendiri - tidak dibagi ke service lain.
// Ini menerapkan prinsip microservice: setiap service
// memiliki datanya sendiri).
// ==================================================
function bacaPeminjaman() {
    try {
        if (!fs.existsSync(LOANS_FILE)) {
            fs.writeFileSync(LOANS_FILE, "{}", "utf-8");
        }
        const raw = fs.readFileSync(LOANS_FILE, "utf-8");
        return JSON.parse(raw || "{}");
    } catch (err) {
        console.error("Gagal membaca data peminjaman, reset ke kosong:", err);
        return {};
    }
}

function simpanPeminjaman(data) {
    fs.writeFileSync(LOANS_FILE, JSON.stringify(data, null, 2), "utf-8");
}

// ==================================================
// MIDDLEWARE: requireAuth
// Memanggil auth-service lewat API (bukan mengecek
// token sendiri) supaya logika autentikasi tetap
// terpusat di satu service.
// ==================================================
async function requireAuth(req, res, next) {
    const authHeader = req.headers.authorization || "";

    if (!authHeader.startsWith("Bearer ")) {
        return res.status(401).json({ error: "Silakan login terlebih dahulu." });
    }

    try {
        const response = await fetch(`${AUTH_SERVICE_URL}/api/auth/verify`, {
            method: "GET",
            headers: { Authorization: authHeader }
        });

        const data = await response.json();

        if (!response.ok || !data.valid) {
            return res.status(401).json({ error: "Sesi tidak valid, silakan login kembali." });
        }

        req.nim = data.nim;
        req.nama = data.nama;
        next();
    } catch (err) {
        console.error("Gagal menghubungi auth-service:", err.message);
        return res.status(503).json({ error: "Layanan autentikasi sedang tidak dapat diakses." });
    }
}

function hitungBukuAktif(peminjaman, nim) {
    return Object.values(peminjaman).filter((p) => String(p.nimMahasiswa) === String(nim)).length;
}

// ==================================================
// HEALTH CHECK
// ==================================================
app.get("/api/health", (req, res) => {
    res.json({ service: "library-service", status: "ok", time: new Date().toISOString() });
});

// ==================================================
// GET /api/books
// Publik (tidak wajib login) - hanya melihat daftar buku
// beserta status ketersediaannya (AC-01, AC-02, AC-03).
// ==================================================
app.get("/api/books", (req, res) => {
    try {
        const daftarBuku = bacaBuku();
        const peminjaman = bacaPeminjaman();

        const hasil = daftarBuku.map((buku) => {
            const dataPinjam = peminjaman[buku.id];
            return {
                id: buku.id,
                judul: buku.judul,
                penulis: buku.penulis,
                status: dataPinjam ? "dipinjam" : "tersedia"
            };
        });

        res.json(hasil);
    } catch (err) {
        console.error(err);
        res.status(500).json({ error: "Gagal mengambil daftar buku." });
    }
});

// ==================================================
// GET /api/loans
// Butuh login. Hanya menampilkan peminjaman milik
// mahasiswa yang sedang login (bukan seluruh mahasiswa).
// ==================================================
app.get("/api/loans", requireAuth, (req, res) => {
    try {
        const peminjaman = bacaPeminjaman();

        const punyaSaya = Object.values(peminjaman).filter(
            (p) => String(p.nimMahasiswa) === String(req.nim)
        );

        res.json({
            nim: req.nim,
            jumlahAktif: punyaSaya.length,
            maksimal: MAX_BUKU_AKTIF,
            loans: punyaSaya
        });
    } catch (err) {
        console.error(err);
        res.status(500).json({ error: "Gagal mengambil data peminjaman." });
    }
});

// ==================================================
// POST /api/loans
// Body: { bookId }
// Menerapkan seluruh aturan bisnis RB-02, RB-03, RB-04, RB-05.
// ==================================================
app.post("/api/loans", requireAuth, (req, res) => {
    const { bookId } = req.body || {};
    const idBuku = Number(bookId);

    if (!idBuku || Number.isNaN(idBuku)) {
        return res.status(400).json({ error: "bookId tidak valid." });
    }

    try {
        const daftarBuku = bacaBuku();
        const buku = daftarBuku.find((b) => b.id === idBuku);

        if (!buku) {
            return res.status(404).json({ error: "Data buku tidak ditemukan." });
        }

        const peminjaman = bacaPeminjaman();

        // RB-04: buku yang sedang dipinjam tidak dapat dipinjam mahasiswa lain
        if (peminjaman[idBuku]) {
            return res.status(409).json({ error: "Buku sedang dipinjam." });
        }

        // RB-03: maksimal 3 buku aktif per mahasiswa
        const jumlahAktif = hitungBukuAktif(peminjaman, req.nim);
        if (jumlahAktif >= MAX_BUKU_AKTIF) {
            return res.status(409).json({ error: `Maksimal peminjaman adalah ${MAX_BUKU_AKTIF} buku aktif.` });
        }

        // RB-05: lama peminjaman 7 hari
        const sekarang = new Date();
        const batasPengembalian = new Date(sekarang);
        batasPengembalian.setDate(batasPengembalian.getDate() + LAMA_PEMINJAMAN_HARI);

        const loan = {
            idBuku: buku.id,
            judulBuku: buku.judul,
            penulisBuku: buku.penulis,
            nimMahasiswa: req.nim,
            tanggalPinjam: sekarang.toISOString(),
            tanggalKembali: batasPengembalian.toISOString()
        };

        peminjaman[idBuku] = loan;
        simpanPeminjaman(peminjaman);

        res.status(201).json({ message: "Buku berhasil dipinjam.", loan });
    } catch (err) {
        console.error(err);
        res.status(500).json({ error: "Gagal memproses peminjaman." });
    }
});

// ==================================================
// POST /api/system/reset
// Setara fitur "Restart Sistem" pada versi awal:
// menghapus seluruh data peminjaman (bukan sesi login).
// ==================================================
app.post("/api/system/reset", requireAuth, (req, res) => {
    try {
        simpanPeminjaman({});
        res.json({ message: "Sistem berhasil direset. Semua buku kembali tersedia." });
    } catch (err) {
        console.error(err);
        res.status(500).json({ error: "Gagal mereset sistem." });
    }
});

app.listen(PORT, () => {
    console.log(`[library-service] berjalan di port ${PORT}`);
    console.log(`[library-service] auth-service diakses di ${AUTH_SERVICE_URL}`);
});
