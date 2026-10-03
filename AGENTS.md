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

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.5. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `bun run build`, `bun run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record a rule with `record-rule` only when the user explicitly asks for one. Instructions for the work at hand are not rules, no matter how emphatic: "remove this typo", "use X here" are work to do, not rules to record. Never record a rule on your own initiative, as a byproduct of a change, or to summarize what you just did. When the user does ask, pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Use `record-rule` rather than your native memory or notes tool, because native memory is personal and session-scoped, while only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `bun run build` or ask the user to run `bun run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>
