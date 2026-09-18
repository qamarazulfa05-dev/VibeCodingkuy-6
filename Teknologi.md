**Daftar Teknologi yang Digunakan**

Pada tahap pengembangan ini, arsitektur aplikasi diubah dari yang sebelumnya bersifat monolitik menjadi arsitektur microservice, sehingga turut terjadi perubahan pada teknologi yang digunakan di setiap komponennya. Berikut perbandingan teknologi sebelum dan sesudah pengembangan:

**Sebelum dikembangkan menggunakan teknologi ini**

Frontend: HTML5, CSS3, Vanilla JavaScript
Backend: tidak ada
Autentikasi: dicek langsung di JS dengan data hardcode
Komunikasi antar service: tidak ada
Penyimpanan data: localStorage di browser
Containerization: tidak ada

**Setelah dikembangkan menggunakan teknologi yang lebih sesuai dengan kebutuhan microservice**

Frontend: HTML5, CSS3, Vanilla JavaScript (tetap, tapi sekarang memanggil REST API)
Backend: Node.js 20 + Express, dipecah jadi 2 service terpisah
Autentikasi: JSON Web Token (JWT), password di-hash dengan bcryptjs
Komunikasi antar service: REST API lewat fetch bawaan Node.js
Penyimpanan data: file JSON per service (students.json, books.json, loans.json) — masing-masing berperan sebagai "database" milik service-nya sendiri
Containerization: Docker + Docker Compose

Catatan: penyimpanan berbasis file JSON dipakai agar setup tetap ringan untuk kebutuhan praktikum. Untuk produksi, data/*.json pada masing-masing service dapat diganti dengan database sungguhan (mis. PostgreSQL untuk auth-service, MongoDB/PostgreSQL untuk library-service) tanpa mengubah kontrak API antar service.
