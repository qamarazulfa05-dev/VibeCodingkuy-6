# ERD Perpustakaan API

```mermaid
erDiagram
    students ||--o{ borrowings : "meminjam"
    books    ||--o{ borrowings : "dipinjam"
    students ||--o{ personal_access_tokens : "punya token"

    students {
        bigint id PK
        varchar nim UK
        varchar nama
        varchar password "hash bcrypt"
    }
    books {
        bigint id PK
        varchar judul
        varchar penulis
    }
    borrowings {
        bigint id PK
        bigint book_id FK
        bigint student_id FK
        datetime tanggal_pinjam
        datetime tanggal_jatuh_tempo "pinjam + 7 hari"
        datetime tanggal_dikembalikan "NULL = belum"
        varchar status "dipinjam | dikembalikan"
        bigint active_book_id UK "book_id saat aktif, NULL saat kembali"
    }
```

Ketersediaan buku **tidak** disimpan di tabel `books`; dihitung dari ada/tidaknya baris `borrowings`
dengan `status = 'dipinjam'` untuk buku itu. Dengan begitu status buku tidak mungkin tidak sinkron dengan transaksi.

| Aturan | Dijaga oleh |
|---|---|
| BR-01 maks. 3 buku aktif | `BorrowingController::store` (transaksi + `lockForUpdate` pada baris mahasiswa) |
| BR-02 buku tidak tersedia tidak bisa dipinjam | cek di `store` + `UNIQUE(active_book_id)` sebagai jaring pengaman |
| BR-03 jatuh tempo = pinjam + 7 hari | `BorrowingController::store` |
