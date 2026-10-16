# Log Prompt AI

Pola kerja: Requirement/Context → Prompt → Output AI → Review manusia → Testing → Iterasi.

> **Kelompok wajib melengkapi bagian "Review manusia" dan "Hasil testing" sendiri**, dan menambah
> baris untuk setiap prompt penting yang dipakai berikutnya (bukan hanya yang di bawah ini).

## Prompt 1: Audit proyek terhadap komponen UTS dan perbaikannya

- **Tanggal:** 15 Oktober 2026
- **AI assistant:** Claude (Anthropic)
- **Context yang diberikan:** PDF "Template PRD dan Instruksi UTS", seluruh source proyek Tugas 3 (Laravel + MySQL, zip).
- **Prompt:**
  > "saya mau kamu perbaiki apa aja yang kurang dari proyek tersebut sesuai dengan komponen uts yang dibutuhkan"
- **Output AI:**
  - Audit kekurangan vs rubrik: tidak ada pengembalian buku dan status pengembalian, tidak ada `GET /books/{id}`,
    endpoint di luar scope (`/system/reset`), penamaan tidak sesuai PRD, password seeder tidak sama dengan README,
    dan tidak ada PRD, API Contract, Code Review Log, serta struktur folder UTS.
  - Perubahan kode: migration `borrowings`, `Borrowing`/`Book` model, `BorrowingController`, `BookController::show`, route,
    tes PHPUnit, Postman collection.
  - Dokumen: `docs/PRD.md`, `docs/API-CONTRACT.md`, `docs/CODE_REVIEW_LOG.md`, `database/schema.sql`, `database/ERD.md`, `README.md`.
- **Review manusia:** [isi: siapa yang membaca kode, apa yang dipahami, apa yang diubah]
- **Hasil testing:** [isi setelah menjalankan `php artisan test` dan Postman Collection Runner; tempel ringkasan]
- **Iterasi:** [isi jika ada error yang ditemukan dan prompt perbaikannya]

## Template prompt berikutnya

```
## Prompt N: [judul pekerjaan]
- Tanggal / AI assistant:
- Context yang diberikan:
- Prompt (tempel apa adanya):
- Output AI (ringkas):
- Review manusia (apa yang diperiksa/diubah, oleh siapa):
- Hasil testing:
- Iterasi / perbaikan:
```
