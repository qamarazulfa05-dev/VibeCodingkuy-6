# Perpustakaan Microservices

## Nama Kelompok

**VibeCodingkuy-6**

**Anggota Kelompok:**

1. Nor Dede Sanjaya (2441919051)
2. Simbi Meylani Putri (2441919043)
3. Qamara Zulfa (2441919036)
4. Riya Monika (2441919049)
5. Nita Abidah (2441919014)
6. Nadila Syafanah (2441919021)

## Deskripsi

Perpustakaan Microservices merupakan aplikasi perpustakaan berbasis microservice yang digunakan untuk 
membantu mahasiswa dalam melakukan proses login, melihat daftar buku, melihat ketersediaan buku, dan melakukan peminjaman buku. 
Aplikasi menggunakan beberapa service yang saling berkomunikasi melalui API.

## Fitur

* Login mahasiswa menggunakan nama dan NIM.
* Menampilkan daftar buku.
* Menampilkan status ketersediaan buku.
* Melakukan peminjaman buku yang tersedia.
* Membatasi jumlah peminjaman aktif maksimal 3 buku untuk setiap mahasiswa.
* Menampilkan informasi peminjaman.

## Arsitektur Sistem

Aplikasi menggunakan arsitektur microservice yang terdiri dari beberapa bagian:

* **Frontend** — menyediakan tampilan aplikasi dan menerima interaksi pengguna.
* **Auth Service** — menangani proses login dan autentikasi mahasiswa.
* **Library Service** — mengelola data buku dan status ketersediaan buku.
* **API Gateway** — menjadi penghubung komunikasi antara frontend dan service melalui API.

Komunikasi antar-service menggunakan **REST API** sehingga setiap service dapat menjalankan fungsi secara terpisah.
