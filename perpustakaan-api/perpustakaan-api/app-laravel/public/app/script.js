document.addEventListener("DOMContentLoaded", function () {

    // ==================================================
    // ELEMENT HTML
    // ==================================================

    const loginPage = document.getElementById("loginPage");
    const mainPage = document.getElementById("mainPage");

    const nimMahasiswa = document.getElementById("nimMahasiswa");
    const passwordMahasiswa = document.getElementById("passwordMahasiswa");
    const loginButton = document.getElementById("loginButton");
    const logoutButton = document.getElementById("logoutButton");
    const restartButton = document.getElementById("restartButton");
    const loginError = document.getElementById("loginError");
    const nimUser = document.getElementById("nimUser");
    const bookList = document.getElementById("bookList");
    const loanInformation = document.getElementById("loanInformation");

    const apiStatus = document.getElementById("apiStatus");


    // ==================================================
    // SESI LOGIN (disimpan di localStorage agar tetap
    // login ketika halaman di-refresh; hanya token &
    // NIM yang disimpan di browser, BUKAN password.
    // Semua data buku & peminjaman kini datang dari
    // API Laravel, bukan dari localStorage.)
    // ==================================================

    function ambilSesi() {
        try {
            const raw = localStorage.getItem("sesiMahasiswa");
            return raw ? JSON.parse(raw) : null;
        } catch (error) {
            localStorage.removeItem("sesiMahasiswa");
            return null;
        }
    }

    function simpanSesi(sesi) {
        localStorage.setItem("sesiMahasiswa", JSON.stringify(sesi));
    }

    function hapusSesi() {
        localStorage.removeItem("sesiMahasiswa");
    }

    let sesiMahasiswa = ambilSesi();


    // ==================================================
    // HELPER: PANGGIL API
    // ==================================================

    async function panggilAPI(url, options = {}) {
        const sesi = ambilSesi();

        const headers = Object.assign(
            { "Content-Type": "application/json" },
            options.headers || {}
        );

        if (sesi && sesi.token) {
            headers["Authorization"] = "Bearer " + sesi.token;
        }

        let response;

        try {
            response = await fetch(url, Object.assign({}, options, { headers }));
        } catch (error) {
            throw new Error("Tidak dapat menghubungi API. Periksa apakah server Laravel sudah berjalan.");
        }

        let data = null;
        try {
            data = await response.json();
        } catch (error) {
            data = null;
        }

        if (response.status === 401) {
            // Sesi sudah tidak valid (token kedaluwarsa / dihapus) -> paksa logout
            hapusSesi();
            tampilkanHalamanLogin();
            throw new Error((data && data.error) || "Sesi berakhir, silakan login kembali.");
        }

        if (!response.ok) {
            throw new Error((data && data.error) || "Terjadi kesalahan pada server.");
        }

        return data;
    }


    // ==================================================
    // CEK STATUS API (opsional, untuk debugging)
    // ==================================================

    async function cekStatusService() {
        try {
            const res = await fetch(CONFIG.API_BASE_URL + "/api/health");
            const data = await res.json();
            if (res.ok && data.database === "ok") {
                apiStatus.textContent = "online";
                apiStatus.className = "status-ok";
            } else {
                throw new Error();
            }
        } catch (error) {
            apiStatus.textContent = "offline";
            apiStatus.className = "status-down";
        }
    }


    // ==================================================
    // LOGIN (POST /api/auth/login)
    // ==================================================

    loginButton.addEventListener("click", async function () {

        const nim = nimMahasiswa.value.trim();
        const password = passwordMahasiswa.value;

        // ==================================================
        // VALIDASI DI SISI FRONTEND (validasi utama tetap
        // dilakukan di API, ini hanya agar pengguna
        // dapat feedback cepat tanpa menunggu jaringan)
        // ==================================================

        if (nim === "") {
            loginError.textContent = "NIM wajib diisi.";
            nimMahasiswa.focus();
            return;
        }

        if (!/^[0-9]+$/.test(nim)) {
            loginError.textContent = "NIM hanya boleh berisi angka.";
            nimMahasiswa.focus();
            return;
        }

        if (nim.length < 5) {
            loginError.textContent = "NIM harus terdiri dari minimal 5 angka.";
            nimMahasiswa.focus();
            return;
        }

        if (password === "") {
            loginError.textContent = "Password wajib diisi.";
            passwordMahasiswa.focus();
            return;
        }

        if (password.length < 6) {
            loginError.textContent = "Password harus terdiri dari minimal 6 karakter.";
            passwordMahasiswa.focus();
            return;
        }

        loginButton.disabled = true;
        loginButton.textContent = "Memproses...";

        try {

            const response = await fetch(CONFIG.API_BASE_URL + "/api/auth/login", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ nim, password })
            });

            const data = await response.json();

            if (!response.ok) {
                loginError.textContent = data.error || "NIM atau Password salah.";
                passwordMahasiswa.focus();
                return;
            }

            sesiMahasiswa = { token: data.token, nim: data.nim, nama: data.nama };
            simpanSesi(sesiMahasiswa);

            loginError.textContent = "";
            await tampilkanHalamanUtama();

        } catch (error) {
            loginError.textContent = error.message || "Tidak dapat menghubungi API.";
        } finally {
            loginButton.disabled = false;
            loginButton.textContent = "Login";
        }

    });


    // ==================================================
    // LOGIN DENGAN ENTER
    // ==================================================

    [nimMahasiswa, passwordMahasiswa].forEach(function (input) {
        input.addEventListener("keydown", function (event) {
            if (event.key === "Enter") {
                loginButton.click();
            }
        });
    });


    // ==================================================
    // LOGOUT
    // ==================================================

    logoutButton.addEventListener("click", function () {
        hapusSesi();
        sesiMahasiswa = null;
        tampilkanHalamanLogin();
    });


    // ==================================================
    // RESTART SISTEM (POST /api/system/reset)
    // ==================================================

    restartButton.addEventListener("click", async function () {

        const konfirmasi = confirm(
            "Apakah Anda yakin ingin merestart sistem?\n\n" +
            "Semua data peminjaman akan dihapus dan seluruh buku akan kembali tersedia."
        );

        if (!konfirmasi) return;

        try {
            await panggilAPI(CONFIG.API_BASE_URL + "/api/system/reset", {
                method: "POST"
            });

            await muatDaftarBuku();
            await muatInformasiPeminjaman();

            alert("Sistem berhasil direstart. Semua buku kembali tersedia.");
        } catch (error) {
            alert(error.message);
        }

    });


    // ==================================================
    // TAMPILKAN HALAMAN LOGIN
    // ==================================================

    function tampilkanHalamanLogin() {
        mainPage.classList.add("hidden");
        loginPage.classList.remove("hidden");

        nimMahasiswa.value = "";
        passwordMahasiswa.value = "";
        loginError.textContent = "";
    }


    // ==================================================
    // TAMPILKAN HALAMAN UTAMA
    // ==================================================

    async function tampilkanHalamanUtama() {
        loginPage.classList.add("hidden");
        mainPage.classList.remove("hidden");

        nimUser.textContent = sesiMahasiswa.nim;

        await muatDaftarBuku();
        await muatInformasiPeminjaman();
    }


    // ==================================================
    // MUAT DAFTAR BUKU (GET /api/books, /api/loans)
    // ==================================================

    async function muatDaftarBuku() {

        bookList.innerHTML = "<p>Memuat daftar buku...</p>";

        let daftarBuku;

        try {
            daftarBuku = await panggilAPI(CONFIG.API_BASE_URL + "/api/books");
        } catch (error) {
            bookList.innerHTML = "";
            const pesan = document.createElement("p");
            pesan.className = "error-message";
            pesan.textContent = error.message;
            bookList.appendChild(pesan);
            return;
        }

        bookList.innerHTML = "";

        daftarBuku.forEach(function (buku) {

            const sedangDipinjam = buku.status === "dipinjam";

            const card = document.createElement("div");
            card.className = "book-card";

            const judul = document.createElement("h3");
            judul.className = "book-title";
            judul.textContent = buku.judul;

            const penulis = document.createElement("p");
            penulis.className = "book-author";
            penulis.textContent = "Penulis: " + buku.penulis;

            const status = document.createElement("p");
            status.className = "book-status";

            const tombol = document.createElement("button");
            tombol.type = "button";
            tombol.className = "borrow-button";
            tombol.textContent = "Pinjam";

            if (sedangDipinjam) {
                status.textContent = "Status: Dipinjam";
                status.classList.add("status-borrowed");
                tombol.disabled = true;
            } else {
                status.textContent = "Status: Tersedia";
                status.classList.add("status-available");
                tombol.disabled = false;

                tombol.addEventListener("click", function () {
                    pinjamBuku(buku.id, tombol);
                });
            }

            card.appendChild(judul);
            card.appendChild(penulis);
            card.appendChild(status);
            card.appendChild(tombol);

            bookList.appendChild(card);

        });

    }


    // ==================================================
    // PROSES PEMINJAMAN (POST /api/loans)
    // API memverifikasi token Bearer (Laravel Sanctum)
    // sebelum memproses peminjaman.
    // ==================================================

    async function pinjamBuku(idBuku, tombol) {

        tombol.disabled = true;
        tombol.textContent = "Memproses...";

        try {

            await panggilAPI(CONFIG.API_BASE_URL + "/api/loans", {
                method: "POST",
                body: JSON.stringify({ bookId: idBuku })
            });

            await muatDaftarBuku();
            await muatInformasiPeminjaman();

            alert("Buku berhasil dipinjam.");

        } catch (error) {
            alert(error.message);
            tombol.disabled = false;
            tombol.textContent = "Pinjam";
        }

    }


    // ==================================================
    // FORMAT TANGGAL ISO -> dd/mm/yyyy, hh.mm
    // ==================================================

    function formatTanggal(isoString) {
        const tanggal = new Date(isoString);

        const hari = String(tanggal.getDate()).padStart(2, "0");
        const bulan = String(tanggal.getMonth() + 1).padStart(2, "0");
        const tahun = tanggal.getFullYear();
        const jam = String(tanggal.getHours()).padStart(2, "0");
        const menit = String(tanggal.getMinutes()).padStart(2, "0");

        return hari + "/" + bulan + "/" + tahun + ", " + jam + "." + menit;
    }


    // ==================================================
    // INFORMASI PEMINJAMAN (GET /api/books, /api/loans)
    // ==================================================

    async function muatInformasiPeminjaman() {

        loanInformation.innerHTML = "<p>Memuat informasi peminjaman...</p>";

        let data;

        try {
            data = await panggilAPI(CONFIG.API_BASE_URL + "/api/loans");
        } catch (error) {
            loanInformation.innerHTML = "";
            const pesan = document.createElement("p");
            pesan.className = "error-message";
            pesan.textContent = error.message;
            loanInformation.appendChild(pesan);
            return;
        }

        loanInformation.innerHTML = "";

        const jumlahAktif = document.createElement("p");
        jumlahAktif.className = "loan-date";
        jumlahAktif.innerHTML = "<strong>Buku Aktif: " + data.jumlahAktif + "/" + data.maksimal + "</strong>";
        loanInformation.appendChild(jumlahAktif);

        if (data.loans.length === 0) {
            const emptyLoan = document.createElement("p");
            emptyLoan.className = "empty-loan";
            emptyLoan.textContent = "Belum ada buku yang dipinjam.";
            loanInformation.appendChild(emptyLoan);
            return;
        }

        data.loans.forEach(function (loan) {

            const loanItem = document.createElement("div");
            loanItem.className = "loan-item";

            loanItem.innerHTML = `
                <div class="loan-title">${loan.judulBuku}</div>
                <div class="loan-date">Penulis: ${loan.penulisBuku}</div>
                <div class="loan-date">NIM: ${loan.nimMahasiswa}</div>
                <div class="loan-date">Tanggal pinjam: ${formatTanggal(loan.tanggalPinjam)}</div>
                <div class="loan-date">Batas pengembalian: ${formatTanggal(loan.tanggalKembali)}</div>
            `;

            loanInformation.appendChild(loanItem);

        });

    }


    // ==================================================
    // INISIALISASI HALAMAN
    // ==================================================

    cekStatusService();

    if (sesiMahasiswa && sesiMahasiswa.nim && sesiMahasiswa.token) {
        tampilkanHalamanUtama();
    } else {
        tampilkanHalamanLogin();
    }

});
