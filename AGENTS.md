# AGENTS.md — KETEMU PENS

## 1. Project Overview

**KETEMU PENS** adalah platform digital Lost & Found untuk membantu mahasiswa PENS menemukan kembali barang yang hilang.

**Tagline:**

> Kembali Temukan Barangmu di PENS

KETEMU PENS berfungsi sebagai **jembatan digital antara mahasiswa yang kehilangan barang, mahasiswa yang menemukan barang, dan petugas keamanan (satpam)**.

Sistem tidak menggantikan proses penitipan barang yang sudah berjalan di kampus. Barang fisik tetap diserahkan kepada satpam, sedangkan aplikasi digunakan untuk mengelola informasi, pencarian, klaim, verifikasi, dan proses pengambilan.

---

## 2. Core Concept

Alur utama sistem:

```text
Mahasiswa menemukan barang
        ↓
Melaporkan barang melalui KETEMU PENS
        ↓
Menentukan lokasi penitipan
        ↓
Barang diserahkan kepada satpam
        ↓
Informasi barang tersedia di KETEMU PENS
        ↓
Pemilik mencari barang
        ↓
Pemilik mengajukan klaim
        ↓
Verifikasi kepemilikan
        ↓
Sistem memberikan Pickup Code
        ↓
Pemilik datang ke satpam
        ↓
Pickup Code diverifikasi
        ↓
Barang dikembalikan
```

### Prinsip utama

> Teknologi membantu proses pencarian dan verifikasi, sedangkan manusia tetap memegang peran utama dalam proses penitipan dan pengembalian barang.

---

## 3. Project Goals

Development harus berfokus pada tujuan berikut:

1. Memudahkan mahasiswa melaporkan barang yang ditemukan.
2. Memudahkan mahasiswa mencari barang yang hilang.
3. Memusatkan informasi Lost & Found dalam satu platform.
4. Mengurangi kemungkinan barang ditemukan tetapi tidak diketahui pemiliknya.
5. Menyediakan mekanisme klaim yang aman.
6. Menjaga privasi informasi barang dan pengguna.
7. Mempertahankan peran satpam sebagai pihak yang menyimpan dan menyerahkan barang secara fisik.

---

## 4. User Roles

### 4.1 Mahasiswa Penemu

Mahasiswa yang menemukan barang.

Responsibilities:

- Melaporkan barang temuan.
- Mengisi informasi barang.
- Mengunggah foto jika diperlukan.
- Menentukan lokasi penitipan.
- Menyerahkan barang kepada satpam.

Mahasiswa penemu **tidak bertanggung jawab melakukan proses pengembalian langsung kepada pemilik** melalui aplikasi.

---

### 4.2 Mahasiswa Pemilik

Mahasiswa yang kehilangan barang.

Responsibilities:

- Mencari barang yang hilang.
- Melihat informasi barang yang ditemukan.
- Mengajukan klaim.
- Menjawab pertanyaan verifikasi.
- Menggunakan Pickup Code untuk mengambil barang.

---

### 4.3 Satpam

Satpam merupakan pihak yang menangani barang secara fisik.

Responsibilities:

- Menerima barang dari mahasiswa penemu.
- Menyimpan barang.
- Memverifikasi Pickup Code ketika pemilik mengambil barang.
- Menyerahkan barang kepada pemilik setelah verifikasi berhasil.

Satpam **tidak wajib memasukkan laporan barang ke sistem** dalam alur utama.

---

### 4.4 Admin

Admin bertanggung jawab terhadap pengelolaan sistem.

Responsibilities:

- Moderasi laporan.
- Mengelola laporan yang mencurigakan.
- Menangani penyalahgunaan sistem.
- Mengelola kategori dan konfigurasi sistem.
- Memantau aktivitas sistem.
- Menangani laporan pengguna.

---

## 5. Functional Requirements

### Authentication

Sistem harus menyediakan autentikasi pengguna.

Preferensi:

- Menggunakan akun institusi PENS jika tersedia.
- Jika belum tersedia, gunakan sistem authentication yang aman.
- Password tidak boleh disimpan dalam bentuk plaintext.

---

### Found Item Report

Mahasiswa dapat membuat laporan barang ditemukan.

Minimal data:

- Nama/kategori barang.
- Deskripsi singkat.
- Lokasi ditemukan.
- Waktu ditemukan.
- Foto barang.
- Lokasi penitipan.
- Informasi verifikasi kepemilikan.

Contoh:

```text
Kategori    : Dompet
Warna       : Hitam
Lokasi      : Gedung D4
Waktu       : 14:30
Penitipan   : Pos Satpam Gedung D4
```

---

### Lost Item Report

Mahasiswa dapat melaporkan barang yang hilang.

Minimal data:

- Nama/kategori barang.
- Deskripsi.
- Perkiraan lokasi kehilangan.
- Perkiraan waktu kehilangan.
- Foto jika tersedia.

---

### Search

Pengguna dapat mencari barang berdasarkan:

- Kategori.
- Nama/deskripsi.
- Lokasi.
- Tanggal.
- Status.

Search harus sederhana dan mudah digunakan.

---

### Item Detail

Informasi publik tidak boleh menampilkan seluruh detail barang.

Contoh informasi publik:

```text
Dompet hitam
Ditemukan di Gedung D4
Tanggal: 28 September 2026
Status: Tersedia
```

Detail tertentu harus disembunyikan untuk digunakan sebagai verifikasi kepemilikan.

---

## 6. Ownership Verification

Verifikasi merupakan bagian penting dari sistem.

Jangan menampilkan seluruh informasi barang kepada publik.

Contoh:

Informasi publik:

> Dompet hitam ditemukan di Gedung D4.

Informasi tersembunyi:

> Terdapat stiker tertentu di bagian dalam dompet.

Pemilik harus mengetahui informasi tersebut ketika melakukan klaim.

### Rules

- Jangan menggunakan informasi yang mudah ditebak.
- Jangan menampilkan jawaban verifikasi sebelum klaim.
- Klaim harus diverifikasi sebelum Pickup Code diberikan.
- Sistem harus mencatat hasil proses klaim.

---

## 7. Claim Flow

```text
User menemukan barang yang kemungkinan miliknya
        ↓
User membuka detail barang
        ↓
User memilih "Ajukan Klaim"
        ↓
Sistem memberikan pertanyaan verifikasi
        ↓
User memberikan jawaban
        ↓
Sistem melakukan validasi
        ↓
Jika valid
        ↓
Generate Pickup Code
```

Jika verifikasi gagal:

```text
Verifikasi gagal
        ↓
User dapat mencoba kembali sesuai batas percobaan
```

Sistem harus memiliki rate limit agar proses verifikasi tidak dapat disalahgunakan.

---

## 8. Pickup Code

Pickup Code digunakan sebagai bukti tambahan ketika mengambil barang.

Requirements:

- Tidak mudah ditebak.
- Memiliki masa berlaku.
- Idealnya hanya dapat digunakan satu kali.
- Tidak boleh disimpan dalam bentuk plaintext jika sistem menyimpan kode secara persisten.
- Setelah digunakan, status berubah menjadi `USED`.

Contoh status:

```text
ACTIVE
EXPIRED
USED
CANCELLED
```

---

## 9. Item Status

Gunakan status yang jelas.

Contoh:

```text
REPORTED
WAITING_DEPOSIT
STORED
CLAIMED
VERIFIED
READY_FOR_PICKUP
RETURNED
EXPIRED
REJECTED
```

Jangan menggunakan status yang ambigu seperti:

```text
OK
DONE
PROCESS
```

Status harus menggambarkan kondisi sebenarnya.

---

## 10. Privacy & Security

Security merupakan bagian penting karena sistem dapat menangani data pengguna dan informasi barang.

### Jangan

- Menampilkan data pribadi pengguna secara publik.
- Menampilkan nomor telepon/email pengguna kepada pengguna lain.
- Menyimpan password plaintext.
- Menampilkan jawaban verifikasi.
- Menyimpan secret/API key di repository.
- Membuat Pickup Code yang mudah ditebak.
- Menggunakan default credentials pada production.

### Wajib

- Validasi input.
- Authentication.
- Authorization.
- Rate limiting.
- Secure password hashing.
- HTTPS pada production.
- Secure session/cookie configuration.
- Server-side validation.
- Audit log untuk aktivitas penting.

---

## 11. Personal Data

Gunakan prinsip **data minimization**.

Simpan hanya data yang benar-benar diperlukan.

Contoh data yang sebaiknya tidak ditampilkan secara publik:

```text
Email
Nomor telepon
NIM
Alamat
Informasi pribadi lainnya
```

Jika identitas pengguna diperlukan untuk proses administrasi, informasi tersebut hanya dapat diakses oleh pihak yang memiliki authorization.

---

## 12. File Upload

Jika pengguna dapat mengunggah foto:

- Validasi MIME type.
- Validasi ukuran file.
- Batasi ekstensi.
- Jangan mempercayai filename dari user.
- Gunakan filename yang dibuat server.
- Simpan file di storage yang aman.
- Jangan menjalankan file yang di-upload sebagai executable.
- Pertimbangkan image processing untuk menghilangkan metadata sensitif.

Contoh batas:

```text
Maximum file size: 5 MB
Allowed types:
- image/jpeg
- image/png
- image/webp
```

---

## 13. Authorization

Setiap endpoint harus memeriksa hak akses pengguna.

Contoh:

```text
Student
    ↓
Can create report

Student
    ↓
Can claim item

Admin
    ↓
Can moderate reports
```

Jangan hanya mengandalkan ID yang dikirim dari frontend.

Contoh yang tidak aman:

```http
GET /api/items/123
```

dengan asumsi semua pengguna boleh mengakses seluruh data.

Backend harus tetap melakukan authorization check.

---

## 14. API Rules

API harus:

- Menggunakan HTTP status code yang sesuai.
- Melakukan input validation.
- Mengembalikan response yang konsisten.
- Tidak membocorkan informasi internal.
- Tidak mengembalikan stack trace pada production.
- Memiliki authentication dan authorization.
- Memiliki rate limiting pada endpoint sensitif.

Contoh response:

```json
{
  "success": true,
  "data": {}
}
```

Error:

```json
{
  "success": false,
  "message": "Data tidak dapat diproses."
}
```

Jangan mengembalikan:

```text
SQL error
Stack trace
Database credentials
Internal filesystem path
API key
```

---

## 15. Frontend Guidelines

Frontend harus:

- Sederhana.
- Mobile-friendly.
- Mudah dipahami mahasiswa.
- Menggunakan bahasa Indonesia yang jelas.
- Tidak menggunakan terlalu banyak langkah.
- Menampilkan status proses dengan jelas.
- Memberikan feedback setelah user melakukan aksi.

Contoh:

```text
[ Laporkan Barang Temuan ]

Kategori
[ Dompet ▼ ]

Lokasi ditemukan
[ Gedung D4 ]

Foto
[ Upload Foto ]

Lokasi penitipan
[ Pos Satpam Gedung D4 ▼ ]

[ Kirim Laporan ]
```

---

## 16. UX Principles

Gunakan prinsip:

### Simple

User harus dapat melakukan aksi utama tanpa banyak langkah.

### Clear

Gunakan label dan instruksi yang jelas.

### Transparent

User harus mengetahui status laporan dan klaim.

### Secure

Security tidak boleh mengorbankan usability secara berlebihan.

### Human-centered

Sistem harus membantu manusia, bukan membuat proses menjadi lebih rumit.

---

## 17. Error Handling

Error harus menggunakan bahasa yang mudah dipahami.

Jangan:

```text
500 Internal Server Error
```

sebagai satu-satunya pesan kepada user.

Lebih baik:

```text
Terjadi kesalahan saat mengirim laporan.
Silakan coba kembali beberapa saat lagi.
```

Detail error tetap disimpan di server log untuk developer.

---

## 18. Logging

Log aktivitas penting seperti:

- Login.
- Report creation.
- Claim submission.
- Verification attempt.
- Pickup Code generation.
- Pickup Code usage.
- Admin moderation.
- Status changes.

Jangan menyimpan:

- Password.
- Token authentication.
- API key.
- Secret.
- Jawaban sensitif secara plaintext jika tidak diperlukan.

---

## 19. AI Features

AI bersifat **opsional/future scope**.

AI dapat digunakan untuk:

- Matching laporan barang hilang dan ditemukan.
- Membantu klasifikasi kategori barang.
- Membantu pencarian berdasarkan deskripsi.
- Memberikan rekomendasi kemungkinan barang yang cocok.

AI tidak boleh menjadi satu-satunya penentu kepemilikan barang.

Contoh:

```text
AI menemukan kemungkinan kecocokan
        ↓
User melakukan klaim
        ↓
Sistem melakukan verification
        ↓
Human verification / physical handover
```

Keputusan akhir tetap melalui mekanisme verifikasi manusia dan sistem.

---

## 20. Admin Moderation

Admin dapat:

- Melihat laporan.
- Menandai laporan mencurigakan.
- Menonaktifkan laporan palsu.
- Menangani laporan penyalahgunaan.
- Melihat audit log.

Admin tidak boleh mengubah data penting tanpa audit trail.

Setiap perubahan penting sebaiknya mencatat:

```text
Who
What
When
Previous value
New value
```

---

## 21. Development Priorities

Prioritas implementasi:

### P0 — MVP

- Authentication
- Found Item Report
- Lost Item Report
- Search
- Item Detail
- Claim
- Verification
- Pickup Code
- Item Status

### P1

- Notification
- Admin Dashboard
- Moderation
- History
- Better search/filter

### P2

- AI Matching
- QR Code Pickup
- PENS SSO
- Analytics
- Mobile application

Jangan mengimplementasikan fitur P1/P2 sebelum fitur P0 stabil.

---

## 22. Database Principles

Database harus:

- Menggunakan foreign key jika diperlukan.
- Memiliki timestamp.
- Menghindari duplicate data.
- Memiliki index pada kolom pencarian.
- Menggunakan transaction untuk proses penting.
- Menggunakan migration.
- Tidak melakukan perubahan schema secara manual di production tanpa migration.

Contoh entitas utama:

```text
users
items
lost_reports
found_reports
claims
verifications
pickup_codes
locations
notifications
audit_logs
```

Struktur akhir dapat berubah mengikuti implementasi.

---

## 23. Transactional Operations

Proses penting harus menggunakan database transaction.

Contoh claim verification:

```text
BEGIN TRANSACTION

Validate claim
Validate verification
Create pickup code
Update claim status
Update item status

COMMIT
```

Jika salah satu proses gagal:

```text
ROLLBACK
```

Tujuannya untuk mencegah data berada dalam kondisi tidak konsisten.

---

## 24. Git Workflow

Gunakan branch berdasarkan fitur.

Contoh:

```text
main
develop
feature/authentication
feature/found-item
feature/lost-item
feature/claim
feature/pickup-code
fix/claim-validation
```

Commit harus jelas.

Contoh:

```text
feat: add found item reporting
feat: implement claim verification
fix: prevent duplicate claims
fix: validate pickup code expiration
docs: update lost item flow
```

Hindari commit seperti:

```text
update
fix
test
coba
wkwk
```

---

## 25. Environment Variables

Secret harus berada di environment variable.

Contoh:

```env
DATABASE_URL=
APP_KEY=
JWT_SECRET=
STORAGE_SECRET=
SMTP_PASSWORD=
```

Jangan commit:

```text
.env
.env.local
secrets.json
credentials.json
private keys
```

Repository harus memiliki:

```text
.env.example
```

tanpa nilai secret sebenarnya.

---

## 26. Testing

Minimal lakukan testing untuk:

### Unit Test

- Validation.
- Claim verification.
- Pickup Code generation.
- Status transition.

### Integration Test

- Authentication.
- Report creation.
- Claim flow.
- Verification flow.
- Pickup flow.

### Security Test

- Unauthorized access.
- IDOR.
- Rate limiting.
- Invalid file upload.
- Authentication bypass.
- Privilege escalation.

### Manual Test

Pastikan alur utama dapat dilakukan dari awal sampai akhir:

```text
Find Item
→ Report
→ Deposit
→ Search
→ Claim
→ Verify
→ Pickup Code
→ Pickup
→ Returned
```

---

## 27. Status Transition Rules

Status tidak boleh berubah secara sembarangan.

Contoh:

```text
REPORTED
    ↓
WAITING_DEPOSIT
    ↓
STORED
    ↓
CLAIMED
    ↓
VERIFIED
    ↓
READY_FOR_PICKUP
    ↓
RETURNED
```

Tidak diperbolehkan melakukan perubahan seperti:

```text
RETURNED → REPORTED
```

kecuali melalui mekanisme administratif yang memang diperlukan.

---

## 28. Human-Centered Design

KETEMU PENS harus mempertahankan prinsip bahwa teknologi adalah alat bantu.

### Sistem menangani

- Penyimpanan informasi.
- Pencarian.
- Filtering.
- Claim.
- Verification.
- Pickup Code.
- Status tracking.

### Manusia menangani

- Menemukan barang.
- Menyerahkan barang kepada satpam.
- Menyimpan barang secara fisik.
- Memverifikasi identitas saat pengambilan.
- Menyerahkan barang kepada pemilik.

Sistem tidak boleh membuat satpam harus melakukan pekerjaan digital yang sebenarnya tidak diperlukan.

---

## 29. Important Product Rule

**Jangan mengubah alur utama hanya karena secara teknis terdapat cara yang lebih mudah.**

Contoh:

Jangan memaksa satpam memasukkan setiap barang ke aplikasi jika mahasiswa penemu dapat membuat laporan sendiri.

Model utama:

```text
Mahasiswa Penemu
    ↓
KETEMU PENS
    ↓
Satpam
```

Bukan:

```text
Mahasiswa Penemu
    ↓
Satpam
    ↓
Satpam harus input ulang ke sistem
```

Tujuan sistem adalah mengurangi pekerjaan manual, bukan memindahkannya ke pihak lain.

---

## 30. Definition of Done

Sebuah fitur dianggap selesai jika:

- Requirement sudah terpenuhi.
- UI dapat digunakan.
- Backend validation tersedia.
- Authorization tersedia.
- Error handling tersedia.
- Test telah dilakukan.
- Tidak terdapat secret di repository.
- Dokumentasi diperbarui jika diperlukan.
- Tidak merusak fitur existing.

---

## 31. Out of Scope for MVP

Jangan mengimplementasikan hal berikut sebelum MVP selesai:

- Native Android/iOS application.
- AI matching kompleks.
- Facial recognition.
- GPS real-time tracking.
- Automatic CCTV integration.
- Blockchain.
- Payment system.
- Complex recommendation system.

Fokus MVP adalah menyelesaikan masalah utama:

> **Membantu mahasiswa PENS menemukan informasi barang yang hilang dan menghubungkannya dengan barang temuan yang dititipkan kepada satpam.**

---

## 32. Development Philosophy

Dalam setiap keputusan teknis, prioritaskan:

1. Correctness
2. Security
3. Simplicity
4. Usability
5. Maintainability
6. Performance

Jangan menambahkan teknologi hanya karena terlihat modern.

Gunakan teknologi yang benar-benar membantu kebutuhan KETEMU PENS.

---

## 33. Final Principle

> **KETEMU PENS bukan sekadar aplikasi Lost & Found. KETEMU PENS adalah jembatan digital yang membantu manusia saling menemukan kembali barang yang hilang dengan proses yang sederhana, aman, dan terorganisir.**