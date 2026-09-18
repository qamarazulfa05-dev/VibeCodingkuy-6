/**
 * ==================================================
 * AUTH SERVICE
 * ==================================================
 * Tanggung jawab service ini HANYA satu hal:
 * mengelola identitas & autentikasi mahasiswa.
 *
 * Endpoint:
 *  POST /api/auth/login   -> login dengan NIM & password, hasilkan JWT
 *  GET  /api/auth/verify  -> verifikasi token JWT (dipanggil oleh
 *                            service lain, mis. library-service,
 *                            lewat HTTP - ini yang membuat komunikasi
 *                            antar service terjadi lewat API)
 *  GET  /api/health       -> health check
 *
 * Service ini TIDAK tahu apa-apa soal buku atau peminjaman.
 * Itu tanggung jawab library-service.
 * ==================================================
 */

const express = require("express");
const cors = require("cors");
const jwt = require("jsonwebtoken");
const bcrypt = require("bcryptjs");
const fs = require("fs");
const path = require("path");

const app = express();

app.use(cors());
app.use(express.json());

const PORT = process.env.PORT || 4001;
const JWT_SECRET = process.env.JWT_SECRET || "dev-secret-jangan-dipakai-produksi";
const JWT_EXPIRES_IN = "2h";

const STUDENTS_FILE = path.join(__dirname, "data", "students.json");

function bacaMahasiswa() {
    const raw = fs.readFileSync(STUDENTS_FILE, "utf-8");
    return JSON.parse(raw);
}

// ==================================================
// HEALTH CHECK
// ==================================================
app.get("/api/health", (req, res) => {
    res.json({ service: "auth-service", status: "ok", time: new Date().toISOString() });
});

// ==================================================
// LOGIN
// Validasi sama seperti aturan bisnis awal (RB-01):
// - NIM wajib, hanya angka, minimal 5 digit
// - Password wajib, minimal 6 karakter
// ==================================================
app.post("/api/auth/login", async (req, res) => {
    const { nim, password } = req.body || {};

    if (!nim || typeof nim !== "string" || nim.trim() === "") {
        return res.status(400).json({ error: "NIM wajib diisi." });
    }

    const nimTrim = nim.trim();

    if (!/^[0-9]+$/.test(nimTrim)) {
        return res.status(400).json({ error: "NIM hanya boleh berisi angka." });
    }

    if (nimTrim.length < 5) {
        return res.status(400).json({ error: "NIM harus terdiri dari minimal 5 angka." });
    }

    if (!password || password.length < 6) {
        return res.status(400).json({ error: "Password harus terdiri dari minimal 6 karakter." });
    }

    let mahasiswa;
    try {
        const daftarMahasiswa = bacaMahasiswa();
        mahasiswa = daftarMahasiswa.find((m) => m.nim === nimTrim);
    } catch (err) {
        console.error("Gagal membaca data mahasiswa:", err);
        return res.status(500).json({ error: "Terjadi kesalahan pada server." });
    }

    if (!mahasiswa) {
        return res.status(401).json({ error: "NIM atau Password salah." });
    }

    const cocok = await bcrypt.compare(password, mahasiswa.passwordHash);

    if (!cocok) {
        return res.status(401).json({ error: "NIM atau Password salah." });
    }

    const token = jwt.sign(
        { sub: mahasiswa.nim, nama: mahasiswa.nama },
        JWT_SECRET,
        { expiresIn: JWT_EXPIRES_IN }
    );

    return res.json({
        token,
        nim: mahasiswa.nim,
        nama: mahasiswa.nama,
        expiresIn: JWT_EXPIRES_IN
    });
});

// ==================================================
// VERIFY TOKEN
// Endpoint ini yang dipanggil library-service lewat
// HTTP setiap kali ada request yang butuh login,
// supaya library-service tidak perlu tahu detail
// bagaimana login bekerja (satu sumber kebenaran).
// ==================================================
app.get("/api/auth/verify", (req, res) => {
    const authHeader = req.headers.authorization || "";
    const token = authHeader.startsWith("Bearer ") ? authHeader.slice(7) : null;

    if (!token) {
        return res.status(401).json({ valid: false, error: "Token tidak ditemukan." });
    }

    try {
        const payload = jwt.verify(token, JWT_SECRET);
        return res.json({ valid: true, nim: payload.sub, nama: payload.nama });
    } catch (err) {
        return res.status(401).json({ valid: false, error: "Token tidak valid atau kedaluwarsa." });
    }
});

app.listen(PORT, () => {
    console.log(`[auth-service] berjalan di port ${PORT}`);
});
