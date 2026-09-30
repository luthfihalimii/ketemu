# PRD — KETEMU PENS

**Product Requirements Document**  
**Nama Produk:** KETEMU PENS  
**Tagline:** *Kembali Temukan Barangmu di PENS*  
**Versi:** 1.0  
**Status:** Draft  
**Target Platform:** Web Responsive / Mobile Web  
**Lingkup:** Politeknik Elektronika Negeri Surabaya (PENS)

---

## 1. Ringkasan Produk

**KETEMU PENS** adalah platform digital Lost & Found yang membantu mahasiswa PENS menemukan kembali barang yang hilang dan membantu mahasiswa yang menemukan barang untuk melaporkannya secara terstruktur.

KETEMU PENS tidak menggantikan proses penitipan barang yang sudah berjalan di kampus. Barang yang ditemukan oleh mahasiswa tetap dapat dititipkan kepada petugas keamanan/satpam. Sistem berfungsi sebagai **jembatan informasi digital** antara penemu, pemilik barang, dan lokasi penitipan.

Konsep utama:

> **Temukan informasinya secara digital, kembalikan barangnya secara langsung dan terverifikasi.**

---

## 2. Latar Belakang

Kehilangan barang merupakan masalah yang dapat terjadi di lingkungan kampus, seperti kehilangan kartu mahasiswa, dompet, earphone, charger, flashdisk, jaket, helm, dan barang pribadi lainnya.

Di sisi lain, mahasiswa yang menemukan barang biasanya dapat menitipkannya kepada satpam. Permasalahan utamanya bukan selalu pada proses penitipan, tetapi pada **sulitnya pemilik mengetahui bahwa barangnya telah ditemukan, di mana barang tersebut berada, dan bagaimana cara mengambilnya**.

Informasi mengenai barang hilang atau ditemukan juga dapat tersebar melalui percakapan pribadi atau grup pesan sehingga sulit dicari kembali.

KETEMU PENS hadir untuk membuat informasi tersebut lebih terpusat dan mudah diakses.

---

## 3. Problem Statement

> Mahasiswa PENS yang kehilangan barang sering kesulitan mengetahui apakah barangnya telah ditemukan dan di mana barang tersebut dititipkan. Sementara itu, mahasiswa yang menemukan barang belum memiliki sarana terpusat untuk menginformasikan barang temuan kepada pemiliknya.

### Masalah yang ingin diselesaikan

1. Informasi barang hilang dan ditemukan belum terpusat.
2. Informasi dari grup chat mudah tenggelam.
3. Pemilik sulit mengetahui lokasi barang yang telah ditemukan.
4. Tidak tersedia proses klaim digital yang terstruktur.
5. Proses pencarian masih membutuhkan komunikasi manual dari satu orang ke orang lain.

---

## 4. Tujuan Produk

### Tujuan Utama

Membangun platform Lost & Found khusus lingkungan PENS yang mempermudah proses:

**Lapor → Cari → Klaim → Verifikasi → Ambil → Kembali**

### Tujuan Spesifik

- Mempermudah mahasiswa melaporkan barang hilang.
- Mempermudah mahasiswa melaporkan barang yang ditemukan.
- Memusatkan informasi Lost & Found PENS.
- Membantu pemilik menemukan informasi barang yang telah ditemukan.
- Menyediakan mekanisme verifikasi sebelum barang diambil.
- Mengurangi penyalahgunaan klaim barang.
- Mempertahankan proses penitipan barang melalui satpam yang sudah berjalan.

---

## 5. Target Pengguna

### 5.1 Mahasiswa PENS

Pengguna utama sistem.

Dapat:
- Melaporkan barang hilang.
- Melaporkan barang ditemukan.
- Mencari barang.
- Mengajukan klaim.
- Melihat status laporan.
- Mendapatkan kode pengambilan setelah klaim diverifikasi.
- Mengonfirmasi bahwa barang telah diterima.

### 5.2 Petugas Keamanan / Satpam

Satpam bukan pengguna utama aplikasi dan tidak bertugas menginput laporan.

Peran utama:
- Menerima dan menyimpan barang temuan.
- Menyerahkan barang kepada pemilik setelah proses verifikasi.
- Memeriksa kode pengambilan jika diperlukan.

### 5.3 Administrator

Administrator digunakan untuk pengelolaan dan moderasi sistem.

Dapat:
- Mengelola laporan.
- Menangani laporan yang bermasalah.
- Menandai konten/laporan yang tidak valid.
- Menangani penyalahgunaan sistem.
- Mengelola kategori dan lokasi.

---

## 6. User Journey

### 6.1 Alur Mahasiswa Menemukan Barang

```text
Mahasiswa menemukan barang
        ↓
Membuka KETEMU PENS
        ↓
Pilih "Laporkan Barang Ditemukan"
        ↓
Mengisi informasi barang
        ↓
Menentukan lokasi penemuan
        ↓
Menentukan lokasi penitipan
        ↓
Mengirim laporan
        ↓
Sistem memberikan instruksi penitipan
        ↓
Barang dititipkan kepada satpam
        ↓
Laporan menjadi tersedia untuk pencarian
```

### 6.2 Alur Mahasiswa Kehilangan Barang

```text
Mahasiswa kehilangan barang
        ↓
Membuka KETEMU PENS
        ↓
Mencari barang
        ↓
Menemukan laporan yang sesuai
        ↓
Membuka detail laporan
        ↓
Mengajukan klaim
        ↓
Menjawab pertanyaan verifikasi
        ↓
Klaim diverifikasi
        ↓
Mendapatkan kode pengambilan
        ↓
Datang ke lokasi penitipan
        ↓
Menunjukkan kode kepada satpam
        ↓
Barang diterima
        ↓
Status menjadi "Dikembalikan"
```

---

## 7. Fitur Utama

### 7.1 Beranda

Menampilkan:
- Search bar.
- Tombol "Laporkan Barang Hilang".
- Tombol "Laporkan Barang Ditemukan".
- Barang terbaru.
- Barang yang baru ditemukan.
- Kategori barang.
- Informasi singkat cara kerja KETEMU PENS.

---

### 7.2 Laporan Barang Hilang

Pengguna dapat membuat laporan barang yang hilang.

Data minimal:
- Nama/kategori barang.
- Foto barang (opsional).
- Deskripsi.
- Lokasi terakhir terlihat.
- Tanggal kehilangan.
- Perkiraan waktu kehilangan.
- Ciri khusus barang.

**Catatan:** Ciri khusus dapat digunakan sebagai informasi verifikasi dan tidak seluruhnya ditampilkan kepada publik.

---

### 7.3 Laporan Barang Ditemukan

Pengguna dapat membuat laporan ketika menemukan barang.

Data minimal:
- Kategori barang.
- Foto barang.
- Deskripsi umum.
- Lokasi ditemukan.
- Tanggal ditemukan.
- Perkiraan waktu ditemukan.
- Lokasi penitipan.
- Catatan tambahan.

Setelah laporan dibuat, sistem mengingatkan pengguna untuk menitipkan barang secara fisik kepada satpam.

---

### 7.4 Pencarian Barang

Pengguna dapat mencari laporan menggunakan:
- Kata kunci.
- Kategori.
- Lokasi.
- Tanggal.
- Status.

Contoh:

> `AirPods`

Sistem menampilkan laporan yang relevan.

---

### 7.5 Detail Barang

Informasi publik dapat meliputi:
- Foto barang.
- Kategori.
- Deskripsi umum.
- Lokasi ditemukan.
- Waktu ditemukan.
- Status.
- Lokasi penitipan.

Informasi sensitif atau ciri khusus tertentu tidak ditampilkan kepada publik.

---

### 7.6 Klaim Barang

Pengguna dapat mengajukan klaim terhadap barang yang dianggap miliknya.

Proses:
1. Klik "Ajukan Klaim".
2. Sistem meminta informasi verifikasi.
3. Pengguna mengisi jawaban.
4. Sistem/administrator melakukan pemeriksaan.
5. Klaim diterima atau ditolak.
6. Jika diterima, sistem membuat kode pengambilan.

---

### 7.7 Verifikasi Kepemilikan

Tujuan fitur ini adalah mencegah orang lain mengambil barang yang bukan miliknya.

Contoh pertanyaan:
- Apa ciri khusus barang?
- Apa isi bagian tertentu dari barang?
- Apa warna/detail yang tidak ditampilkan?
- Kapan terakhir kali barang digunakan?
- Informasi lain yang hanya diketahui pemilik.

**Prinsip keamanan:**

Jangan menampilkan seluruh detail barang temuan secara publik.

Contoh:

**Publik:**
> Dompet hitam ditemukan di Gedung D4.

**Informasi verifikasi:**
> Terdapat stiker tertentu di bagian dalam dompet.

---

### 7.8 Kode Pengambilan

Setelah klaim diterima, sistem menghasilkan kode pengambilan unik.

Contoh:

```text
K7P-291
```

Kode digunakan ketika pemilik mengambil barang di lokasi penitipan.

Kode sebaiknya:
- Sulit ditebak.
- Hanya berlaku untuk satu proses pengambilan.
- Tidak menampilkan informasi sensitif.
- Berstatus expired setelah barang dikembalikan.

---

### 7.9 Status Laporan

Status yang digunakan:

- `OPEN` — laporan aktif.
- `MATCHED` — ditemukan kemungkinan kecocokan.
- `CLAIMED` — terdapat pengguna yang mengajukan klaim.
- `VERIFIED` — klaim telah diverifikasi.
- `READY_FOR_PICKUP` — barang siap diambil.
- `RETURNED` — barang telah dikembalikan.
- `CLOSED` — laporan selesai.
- `REJECTED` — laporan/klaim ditolak.

---

### 7.10 Riwayat

Pengguna dapat melihat:
- Laporan barang hilang.
- Laporan barang ditemukan.
- Klaim yang pernah dibuat.
- Status laporan.
- Riwayat pengembalian.

---

## 8. Fitur Pengembangan / Future Scope

Fitur berikut tidak wajib untuk MVP:

### 8.1 AI Matching

Sistem dapat membantu mencocokkan laporan barang hilang dengan laporan barang ditemukan berdasarkan:
- Nama/kategori.
- Deskripsi.
- Lokasi.
- Waktu.
- Foto.

Contoh:

> Kemungkinan kecocokan: **89%**

AI hanya memberikan rekomendasi dan tidak menjadi penentu final kepemilikan.

### 8.2 Notifikasi

Notifikasi ketika:
- Ada kemungkinan barang yang cocok.
- Klaim diterima.
- Klaim ditolak.
- Barang siap diambil.
- Laporan akan kedaluwarsa.

### 8.3 QR Code

QR Code dapat digunakan untuk:
- Identifikasi proses pengambilan.
- Konfirmasi serah-terima.
- Mengurangi kesalahan pencatatan.

### 8.4 Integrasi Akun PENS

Jika tersedia akses resmi, pengguna dapat login menggunakan akun institusi PENS untuk mengurangi akun palsu.

---

## 9. Non-Functional Requirements

### 9.1 Usability

- Antarmuka sederhana dan mudah dipahami.
- Proses membuat laporan tidak terlalu panjang.
- Sistem responsive pada desktop dan mobile.
- Informasi penting dapat ditemukan dengan cepat.

### 9.2 Performance

Target awal:
- Halaman utama dapat dimuat dengan cepat pada koneksi kampus/seluler yang normal.
- Pencarian tidak membutuhkan proses yang panjang.
- Upload gambar dibatasi ukuran dan dikompresi bila diperlukan.

### 9.3 Availability

Sistem diharapkan dapat tersedia selama mahasiswa membutuhkan akses, terutama pada jam operasional kampus.

### 9.4 Security

- Autentikasi pengguna.
- Otorisasi berbasis peran.
- Validasi input.
- Pembatasan upload file.
- Perlindungan data pribadi.
- Rate limiting.
- Audit log untuk tindakan penting.
- Kode pengambilan harus sulit ditebak.
- Informasi sensitif tidak boleh muncul pada halaman publik.

---

## 10. Privacy & Security Requirements

KETEMU PENS menangani data pengguna dan informasi barang sehingga prinsip privacy-by-design harus diterapkan.

### Data yang perlu dilindungi

- Nama pengguna.
- Identitas akun.
- Informasi kontak.
- Foto barang.
- Detail barang.
- Riwayat klaim.
- Riwayat pengambilan.

### Prinsip

1. Collect minimum data yang diperlukan.
2. Jangan menampilkan data pribadi secara publik.
3. Jangan menampilkan seluruh ciri barang temuan.
4. Batasi akses data berdasarkan role.
5. Simpan password menggunakan hashing yang aman jika sistem menggunakan autentikasi lokal.
6. Gunakan HTTPS.
7. Jangan menyimpan secret/API key di repository.
8. Validasi semua input dari pengguna.
9. Batasi jenis dan ukuran file upload.
10. Catat aktivitas penting melalui audit log.

---

## 11. Moderation & Abuse Prevention

Sistem harus memiliki mekanisme untuk menangani:
- Laporan palsu.
- Spam.
- Klaim palsu.
- Foto yang tidak sesuai.
- Penyalahgunaan sistem.
- Konten yang tidak pantas.

Administrator dapat:
- Menonaktifkan laporan.
- Menolak klaim.
- Menandai akun/laporan mencurigakan.
- Menghapus konten yang melanggar aturan.

---

## 12. MVP Scope

Versi pertama KETEMU PENS cukup memiliki:

### Authentication
- Login.
- Register atau integrasi akun institusi jika tersedia.

### Lost Item
- Buat laporan barang hilang.
- Lihat laporan.
- Cari laporan.

### Found Item
- Buat laporan barang ditemukan.
- Menentukan lokasi ditemukan.
- Menentukan lokasi penitipan.

### Claim
- Ajukan klaim.
- Verifikasi kepemilikan.
- Kode pengambilan.

### Return
- Konfirmasi pengambilan.
- Status barang berubah menjadi `RETURNED`.

### Admin
- Moderasi laporan.
- Moderasi klaim.
- Pengelolaan kategori/lokasi.

---

## 13. Out of Scope untuk MVP

Hal berikut tidak menjadi prioritas pada versi pertama:

- Pembayaran.
- Marketplace.
- Pengiriman barang.
- Live chat kompleks.
- Face recognition.
- Pelacakan GPS pengguna secara realtime.
- Integrasi dengan seluruh sistem akademik PENS.
- AI matching sebagai komponen wajib.

---

## 14. Success Metrics

Keberhasilan MVP dapat diukur melalui:

1. Jumlah laporan barang hilang.
2. Jumlah laporan barang ditemukan.
3. Jumlah barang yang berhasil dipertemukan.
4. Persentase klaim yang berhasil diverifikasi.
5. Jumlah barang yang berhasil dikembalikan.
6. Waktu rata-rata dari laporan hingga barang ditemukan kembali.
7. Jumlah laporan palsu atau klaim yang ditolak.
8. Tingkat penggunaan kembali oleh mahasiswa.

---

## 15. Contoh Skenario Penggunaan

### Skenario A — Barang Hilang

Andi kehilangan earphone di Gedung D4.

1. Andi membuka KETEMU PENS.
2. Andi mencari "earphone".
3. Sistem menampilkan beberapa laporan.
4. Andi menemukan laporan earphone yang sesuai.
5. Andi memilih "Ajukan Klaim".
6. Andi menjawab pertanyaan verifikasi.
7. Klaim diterima.
8. Andi mendapatkan kode pengambilan.
9. Andi datang ke Pos Satpam Gedung D4.
10. Barang diserahkan setelah verifikasi.
11. Status berubah menjadi `RETURNED`.

### Skenario B — Menemukan Barang

Budi menemukan dompet di area kampus.

1. Budi membuka KETEMU PENS.
2. Memilih "Laporkan Barang Ditemukan".
3. Mengisi informasi umum.
4. Menentukan lokasi penemuan.
5. Menentukan lokasi penitipan.
6. Mengirim laporan.
7. Budi menitipkan dompet kepada satpam.
8. Laporan dapat ditemukan oleh pemilik.

---

## 16. Prinsip Human-Centered Design

KETEMU PENS harus mengikuti prinsip:

### Simple

Pengguna dapat membuat laporan tanpa harus memahami teknologi.

### Helpful

Sistem membantu pengguna menemukan informasi yang relevan.

### Transparent

Pengguna mengetahui status laporan dan proses klaim.

### Secure

Sistem mencegah klaim palsu dan melindungi data pengguna.

### Human-in-the-loop

Teknologi membantu proses pencarian dan verifikasi, tetapi keputusan dan proses pengembalian tetap melibatkan manusia.

---

## 17. Arsitektur Konseptual

```text
┌─────────────────────┐
│      Mahasiswa      │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│   KETEMU PENS Web   │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│      Backend API    │
├─────────────────────┤
│ Auth                │
│ Lost & Found        │
│ Claim & Verification│
│ Notification        │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│      Database       │
└─────────────────────┘

Physical World:
Mahasiswa → Satpam → Pengambilan Barang
```

---

## 18. Prioritas Fitur

| Fitur | Prioritas | MVP |
|---|---|---|
| Login | High | Ya |
| Lapor barang hilang | High | Ya |
| Lapor barang ditemukan | High | Ya |
| Search | High | Ya |
| Detail barang | High | Ya |
| Claim | High | Ya |
| Verifikasi | High | Ya |
| Kode pengambilan | High | Ya |
| Status laporan | High | Ya |
| Admin moderation | High | Ya |
| Notification | Medium | Opsional |
| AI Matching | Medium | Tidak |
| QR Code | Low | Tidak |
| Integrasi akun PENS | Low | Tidak |

---

## 19. Acceptance Criteria MVP

### Laporan Barang Hilang

- User dapat membuat laporan.
- Sistem melakukan validasi data.
- Laporan dapat ditemukan melalui pencarian.
- User dapat melihat status laporan.

### Laporan Barang Ditemukan

- User dapat membuat laporan barang ditemukan.
- User dapat menentukan lokasi penemuan.
- User dapat menentukan lokasi penitipan.
- Sistem memberikan instruksi untuk menitipkan barang kepada satpam.

### Klaim

- User dapat mengajukan klaim.
- Sistem meminta informasi verifikasi.
- Klaim dapat diterima atau ditolak.
- Kode pengambilan hanya diberikan setelah klaim diterima.

### Pengembalian

- Pemilik dapat menunjukkan kode pengambilan.
- Barang dapat ditandai sebagai dikembalikan.
- Status laporan berubah menjadi `RETURNED`.
- Riwayat pengembalian tersimpan.

---

## 20. Future Vision

KETEMU PENS dapat berkembang menjadi platform layanan barang temuan terintegrasi di lingkungan kampus.

Pengembangan jangka panjang dapat mencakup:

- AI-powered matching.
- Notifikasi realtime.
- Integrasi akun PENS.
- QR-based handover.
- Dashboard statistik barang hilang.
- Analisis lokasi yang sering menjadi titik kehilangan barang.
- Integrasi dengan aplikasi kampus jika tersedia.

Namun, pengembangan tetap harus mempertahankan prinsip:

> **Teknologi digunakan untuk mempermudah manusia, bukan menambah kerumitan proses.**

---

## 21. Kesimpulan

KETEMU PENS merupakan konsep platform Lost & Found yang berfokus pada penyelesaian masalah nyata di lingkungan PENS.

Sistem tidak menggantikan proses penitipan barang kepada satpam yang sudah berjalan. KETEMU PENS menambahkan lapisan digital untuk membuat informasi barang hilang dan ditemukan lebih terpusat, mudah dicari, serta lebih aman melalui proses verifikasi.

Dengan alur:

**Lapor → Cari → Klaim → Verifikasi → Ambil → Kembali**

KETEMU PENS diharapkan dapat membantu mahasiswa mendapatkan kembali barangnya dengan proses yang lebih terstruktur dan efisien.
