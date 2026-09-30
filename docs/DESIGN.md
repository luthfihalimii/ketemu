# DESIGN.md — KETEMU PENS

## 1. Design Overview

**KETEMU PENS** adalah platform digital Lost & Found untuk lingkungan PENS.

Design system dirancang dengan prinsip:

- Simple
- Modern
- Clean
- Friendly
- Trustworthy
- Mobile-first
- Human-centered

Antarmuka harus terasa seperti aplikasi kampus modern, bukan seperti sistem administrasi yang kaku.

### Tagline

> Kembali Temukan Barangmu di PENS

---

# 2. Design Principles

## 2.1 Simple First

User harus dapat memahami fungsi utama aplikasi tanpa membutuhkan tutorial panjang.

Fungsi utama harus terlihat jelas:

```text
Cari Barang
Laporkan Barang Temuan
Laporkan Barang Hilang
```

---

## 2.2 Mobile First

Mayoritas pengguna akan mengakses aplikasi melalui smartphone.

Prioritas desain:

```text
Mobile
  ↓
Tablet
  ↓
Desktop
```

Semua halaman harus responsive.

---

## 2.3 Human-Centered

Desain harus membantu manusia menyelesaikan masalah, bukan menambah pekerjaan.

Contoh:

Mahasiswa yang menemukan barang cukup:

```text
Laporkan
→ Isi informasi
→ Pilih lokasi penitipan
→ Serahkan barang ke satpam
```

Tidak perlu melewati proses administratif yang tidak diperlukan.

---

## 2.4 Clear Status

User harus selalu mengetahui kondisi barang.

Gunakan status visual yang jelas:

```text
Tersedia
Sedang Diklaim
Siap Diambil
Sudah Dikembalikan
```

Hindari status teknis seperti:

```text
STATUS_01
PROCESSING_STATE
ITEM_VERIFIED_2
```

pada UI pengguna.

---

# 3. Technology Stack

## Frontend

- Laravel Blade
- Tailwind CSS
- Alpine.js jika diperlukan
- Vite

## Backend

- Laravel
- PHP
- Laravel Eloquent
- Laravel Validation
- Laravel Authentication

## Database

Default recommendation:

- PostgreSQL

MySQL/MariaDB tetap dapat digunakan jika deployment membutuhkan.

## Storage

Gunakan Laravel Filesystem.

Contoh:

```text
local
public
S3-compatible storage
```

Untuk production, object storage dapat digunakan jika jumlah gambar berkembang.

---

# 4. Visual Direction

## Overall Style

KETEMU PENS menggunakan visual style:

> **Modern Campus × Friendly Utility**

Karakter desain:

- Clean
- Rounded
- Spacious
- Soft shadow
- Minimal border
- Clear typography
- Large touch target
- Informative cards

Hindari:

- Glassmorphism berlebihan
- Gradient berlebihan
- Animasi berlebihan
- Neon color
- Dashboard yang terlalu kompleks
- Terlalu banyak card dalam satu halaman

---

# 5. Color System

Gunakan warna utama yang terinspirasi dari identitas kampus dan konsep kepercayaan.

### Primary

```text
Primary 50
Primary 100
Primary 200
Primary 300
Primary 400
Primary 500
Primary 600
Primary 700
Primary 800
Primary 900
```

Default primary dapat menggunakan **biru**.

Contoh Tailwind:

```text
blue-50
blue-100
blue-500
blue-600
blue-700
blue-900
```

Primary digunakan untuk:

- CTA
- Link
- Active navigation
- Focus state
- Important information

---

## 5.1 Semantic Colors

### Success

Gunakan green.

```text
green-50
green-600
green-700
```

Digunakan untuk:

- Barang tersedia
- Verifikasi berhasil
- Pickup berhasil
- Success message

---

### Warning

Gunakan amber.

```text
amber-50
amber-500
amber-700
```

Digunakan untuk:

- Menunggu penitipan
- Menunggu verifikasi
- Warning

---

### Danger

Gunakan red.

```text
red-50
red-500
red-600
red-700
```

Digunakan untuk:

- Error
- Klaim ditolak
- Laporan ditolak
- Aksi berbahaya

---

### Neutral

Gunakan neutral/slate untuk sebagian besar UI.

```text
slate-50
slate-100
slate-200
slate-500
slate-700
slate-900
```

---

# 6. Typography

Gunakan font sans-serif modern.

Recommended:

```text
Inter
```

Fallback:

```text
ui-sans-serif
system-ui
sans-serif
```

Hierarchy:

```text
Display
→ text-4xl / text-5xl

Page Heading
→ text-2xl / text-3xl

Section Heading
→ text-xl

Card Heading
→ text-lg

Body
→ text-sm / text-base

Caption
→ text-xs / text-sm
```

Jangan menggunakan terlalu banyak ukuran font dalam satu halaman.

---

# 7. Spacing

Gunakan Tailwind spacing system.

Contoh:

```text
p-4
p-6
p-8

gap-4
gap-6
gap-8

space-y-4
space-y-6
space-y-8
```

Prioritaskan whitespace.

Jangan membuat UI terlalu padat.

---

# 8. Border Radius

Gunakan rounded corners secara konsisten.

Recommended:

```text
rounded-lg
rounded-xl
rounded-2xl
```

Guideline:

| Component | Radius |
|---|---|
| Button | rounded-lg |
| Input | rounded-lg |
| Card | rounded-xl |
| Modal | rounded-2xl |
| Badge | rounded-full |
| Avatar | rounded-full |

---

# 9. Shadows

Gunakan shadow secara minimal.

Recommended:

```text
shadow-sm
shadow
shadow-md
```

Default card:

```text
bg-white
border
shadow-sm
rounded-xl
```

Jangan menggunakan shadow besar pada seluruh component.

---

# 10. Layout

Maximum content width:

```text
max-w-7xl
```

Default page:

```html
<div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
```

Mobile:

```text
px-4
```

Tablet:

```text
sm:px-6
```

Desktop:

```text
lg:px-8
```

---

# 11. Navigation

Desktop navigation:

```text
Logo
Cari Barang
Laporkan
Riwayat
        [Profile]
```

Mobile:

```text
Logo
                Menu
```

Mobile navigation dapat menggunakan bottom navigation jika dibutuhkan.

Recommended primary navigation:

```text
Home
Cari
Laporkan
Riwayat
Profile
```

---

# 12. Landing Page

Landing page harus langsung menjelaskan value proposition.

## Hero

Contoh:

```text
Kehilangan barang di PENS?

Tenang, siapa tahu barangmu
sudah ditemukan.

Cari barang hilang atau laporkan
barang yang kamu temukan.

[ Cari Barang ] [ Laporkan Barang ]
```

Tambahkan visual sederhana yang menggambarkan proses Lost & Found.

---

## Quick Actions

Tampilkan tiga action utama:

```text
┌──────────────────────┐
│ 🔍                   │
│ Cari Barang          │
│ Temukan barangmu     │
└──────────────────────┘

┌──────────────────────┐
│ 📦                   │
│ Laporkan Temuan      │
│ Laporkan barang...   │
└──────────────────────┘

┌──────────────────────┐
│ 📋                   │
│ Barang Hilang        │
│ Laporkan barang...   │
└──────────────────────┘
```

Icon dapat menggunakan:

```text
Lucide Icons
```

---

# 13. Search Page

Search merupakan salah satu fitur paling penting.

Layout:

```text
Cari Barang
────────────────────────────

[ 🔍 Cari nama atau deskripsi ]

Filter:
[ Kategori ]
[ Lokasi ]
[ Tanggal ]

────────────────────────────

Hasil Pencarian

┌────────────────────────────┐
│ Foto                       │
│ Dompet Hitam               │
│ Gedung D4                  │
│ 28 September 2026          │
│                            │
│ ● Tersedia                 │
└────────────────────────────┘
```

---

# 14. Item Card

Card barang harus sederhana.

Contoh:

```text
┌─────────────────────────┐
│                         │
│       Item Image        │
│                         │
├─────────────────────────┤
│ Dompet Hitam            │
│                         │
│ Gedung D4               │
│ 28 September 2026       │
│                         │
│ ● Tersedia              │
└─────────────────────────┘
```

Jangan menampilkan:

- Nomor telepon.
- Email.
- Informasi pribadi.
- Detail rahasia verifikasi.

---

# 15. Item Detail

Halaman detail:

```text
← Kembali

[ Image ]

Dompet Hitam

● Tersedia

Ditemukan
Gedung D4

Tanggal
28 September 2026

Lokasi Penitipan
Pos Satpam Gedung D4

────────────────────

Apakah ini barangmu?

[ Ajukan Klaim ]
```

Detail rahasia tidak ditampilkan.

---

# 16. Found Item Form

Form harus dibuat bertahap jika terlalu panjang.

### Step 1

```text
Informasi Barang

Kategori
[ Pilih kategori ]

Nama barang
[ ................ ]

Deskripsi
[ ................ ]
```

### Step 2

```text
Lokasi & Waktu

Lokasi ditemukan
[ Pilih lokasi ]

Tanggal
[ ........ ]

Waktu
[ ........ ]
```

### Step 3

```text
Foto Barang

[ Upload Foto ]
```

### Step 4

```text
Lokasi Penitipan

Pilih lokasi satpam

[ Pos Satpam Gedung D4 ]

[ Kirim Laporan ]
```

---

# 17. Lost Item Form

Form barang hilang:

```text
Laporkan Barang Hilang

Kategori
[ ........ ]

Nama barang
[ ........ ]

Deskripsi
[ ........ ]

Lokasi terakhir terlihat
[ ........ ]

Perkiraan waktu
[ ........ ]

Foto
[ Upload ]

[ Simpan Laporan ]
```

---

# 18. Claim Interface

Ketika user menemukan barang yang kemungkinan miliknya:

```text
Apakah ini barangmu?

Untuk memastikan barang benar-benar
milikmu, jawab beberapa pertanyaan.

Pertanyaan 1
Detail apa yang terdapat pada barang?

[ ......................... ]

Pertanyaan 2
Di mana terakhir kali kamu melihat barang ini?

[ ......................... ]

[ Kirim Verifikasi ]
```

Gunakan copy yang tidak membuat user merasa sedang diuji secara berlebihan.

---

# 19. Verification Success

Jika berhasil:

```text
✓ Verifikasi Berhasil

Barang cocok dengan informasi
yang kamu berikan.

Pickup Code kamu:

┌─────────────────────┐
│      482 917        │
└─────────────────────┘

Ambil barang di:

Pos Satpam Gedung D4

Kode hanya dapat digunakan
satu kali.

[ Lihat Detail Pengambilan ]
```

---

# 20. Pickup Page

```text
Pickup Barang

Barang
Dompet Hitam

Lokasi
Pos Satpam Gedung D4

Pickup Code
482 917

Status
● Siap Diambil

Tunjukkan kode ini kepada
petugas keamanan.
```

Jangan menampilkan data sensitif yang tidak diperlukan.

---

# 21. Status Badge

Gunakan badge untuk status.

### Available

```html
<span class="rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700">
    Tersedia
</span>
```

### Pending

```html
<span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">
    Menunggu
</span>
```

### Returned

```html
<span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
    Dikembalikan
</span>
```

### Rejected

```html
<span class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700">
    Ditolak
</span>
```

---

# 22. Buttons

Gunakan hierarchy yang jelas.

## Primary

```html
<button class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
    Cari Barang
</button>
```

## Secondary

```html
<button class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
    Batal
</button>
```

## Danger

```html
<button class="rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">
    Hapus
</button>
```

Button harus memiliki:

- Hover state
- Focus state
- Disabled state
- Loading state jika membutuhkan asynchronous request

---

# 23. Forms

Input default:

```html
<input
    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm
           outline-none
           focus:border-blue-500
           focus:ring-2
           focus:ring-blue-500/20"
/>
```

Label harus selalu tersedia.

Jangan mengandalkan placeholder sebagai label.

---

# 24. Empty State

Jika tidak ada hasil:

```text
┌───────────────────────────┐
│                           │
│          🔎               │
│                           │
│ Barang belum ditemukan    │
│                           │
│ Coba gunakan kata kunci   │
│ atau filter yang berbeda. │
│                           │
│ [ Cari Lagi ]             │
│                           │
└───────────────────────────┘
```

Empty state harus membantu user mengambil tindakan berikutnya.

---

# 25. Loading State

Gunakan skeleton/loading indicator.

Contoh:

```text
┌─────────────────────────┐
│ ████████████████        │
│ ██████████              │
│ ████████                │
└─────────────────────────┘
```

Hindari blank screen ketika data sedang dimuat.

---

# 26. Error State

Contoh:

```text
Terjadi kesalahan

Data belum dapat dimuat.
Silakan coba kembali.

[ Coba Lagi ]
```

Error message harus human-readable.

---

# 27. Notification / Toast

Gunakan toast untuk feedback singkat.

### Success

```text
✓ Laporan berhasil dikirim.
```

### Error

```text
× Laporan gagal dikirim.
Silakan coba lagi.
```

### Warning

```text
! Pastikan barang sudah diserahkan kepada satpam.
```

Toast tidak boleh menjadi satu-satunya tempat untuk informasi penting.

---

# 28. Confirmation Modal

Gunakan modal untuk tindakan yang tidak dapat dibatalkan.

Contoh:

```text
Batalkan Laporan?

Laporan yang dibatalkan tidak dapat
digunakan untuk proses klaim.

[ Batal ] [ Ya, Batalkan ]
```

Jangan menggunakan modal untuk setiap interaksi kecil.

---

# 29. Dashboard

Dashboard mahasiswa harus sederhana.

Contoh:

```text
Selamat datang, Luthfi!

┌──────────────┐
│ 2            │
│ Laporan      │
└──────────────┘

┌──────────────┐
│ 1            │
│ Klaim        │
└──────────────┘

Aktivitas Terbaru

• Dompet Hitam
  Klaim berhasil

• Kunci Motor
  Laporan dibuat
```

---

# 30. Admin Dashboard

Admin membutuhkan interface yang lebih information-dense.

Navigation:

```text
Dashboard
Reports
Claims
Users
Locations
Moderation
Audit Logs
Settings
```

Admin dashboard dapat menggunakan:

- Data table
- Filter
- Search
- Status badge
- Modal
- Confirmation dialog

Tetap hindari dashboard yang terlalu kompleks.

---

# 31. Responsive Breakpoints

Gunakan breakpoint Tailwind standar:

```text
sm
md
lg
xl
2xl
```

Guideline:

### Mobile

```text
1 column
Full-width buttons
Stacked forms
Bottom navigation
```

### Tablet

```text
2 columns
```

### Desktop

```text
2–4 columns
Sidebar/dashboard layout
```

---

# 32. Accessibility

UI harus memperhatikan accessibility.

Wajib:

- Semantic HTML.
- Label pada form.
- Keyboard navigation.
- Focus state.
- Kontras warna yang cukup.
- Alt text pada gambar.
- Button tidak hanya menggunakan icon.
- Jangan menyampaikan informasi hanya melalui warna.

Contoh:

Jangan:

```text
●
```

untuk status tanpa teks.

Lebih baik:

```text
● Tersedia
```

---

# 33. Image Guidelines

Foto barang merupakan bagian penting dari sistem.

Image:

- Memiliki aspect ratio konsisten.
- Menggunakan `object-cover`.
- Memiliki fallback jika gambar tidak tersedia.
- Tidak menampilkan informasi sensitif jika tidak diperlukan.

Card image:

```html
<div class="aspect-[4/3] overflow-hidden rounded-xl">
    <img
        src="..."
        alt="Dompet hitam"
        class="h-full w-full object-cover"
    >
</div>
```

---

# 34. Component Architecture

Gunakan reusable Blade components.

Contoh:

```text
resources/views/components/

button.blade.php
input.blade.php
textarea.blade.php
select.blade.php
card.blade.php
badge.blade.php
modal.blade.php
alert.blade.php
empty-state.blade.php
item-card.blade.php
status-badge.blade.php
```

Jangan menduplikasi markup yang sama di banyak halaman.

---

# 35. Laravel View Structure

Recommended:

```text
resources/views/

layouts/
    app.blade.php
    guest.blade.php
    admin.blade.php

components/
    ui/
    items/
    forms/
    navigation/

pages/
    home.blade.php
    search.blade.php
    items/
    reports/
    claims/
    dashboard/
    profile/

admin/
    dashboard.blade.php
    reports/
    claims/
    users/
```

Struktur dapat disesuaikan dengan perkembangan project.

---

# 36. Tailwind Guidelines

Gunakan utility classes secara konsisten.

Prioritaskan:

```text
flex
grid
gap
space
max-w
mx-auto
px
py
rounded
border
shadow
text
bg
hover
focus
disabled
```

Jangan membuat CSS custom jika Tailwind utility sudah cukup.

Custom CSS hanya digunakan jika:

- Utility Tailwind tidak cukup.
- Ada kebutuhan design system khusus.
- Ada third-party component yang membutuhkan override.

---

# 37. Reusable Design Tokens

Jika project berkembang, buat token untuk:

```text
Colors
Spacing
Radius
Typography
Shadows
Transitions
```

Jangan hardcode nilai berbeda-beda untuk component yang seharusnya sama.

Contoh:

```text
Button → rounded-lg
Input → rounded-lg
Card → rounded-xl
Modal → rounded-2xl
```

---

# 38. Animation

Animation harus subtle.

Recommended:

```text
transition
duration-150
duration-200
ease-out
```

Gunakan animation untuk:

- Button interaction.
- Modal.
- Toast.
- Dropdown.
- Loading.

Hindari animasi berlebihan pada halaman utama.

---

# 39. Dark Mode

Dark mode **tidak wajib untuk MVP**.

Jika diimplementasikan:

- Pastikan semua component memiliki dark state.
- Jangan mencampur dark mode hanya pada beberapa halaman.
- Gunakan Tailwind `dark:` variant secara konsisten.

---

# 40. Design Do & Don't

## Do

- Gunakan whitespace.
- Gunakan typography yang jelas.
- Gunakan icon yang konsisten.
- Gunakan CTA yang jelas.
- Gunakan status badge.
- Buat form sederhana.
- Prioritaskan mobile.
- Gunakan reusable components.

## Don't

- Jangan membuat UI terlalu ramai.
- Jangan menggunakan terlalu banyak warna.
- Jangan menggunakan terlalu banyak gradient.
- Jangan menggunakan icon tanpa konteks.
- Jangan menyembunyikan informasi penting.
- Jangan membuat user melewati langkah yang tidak diperlukan.
- Jangan mengorbankan usability demi visual.

---

# 41. Primary User Flow

Design harus mendukung alur berikut:

```text
                    KETEMU PENS
                         │
             ┌───────────┴───────────┐
             │                       │
       Kehilangan Barang       Menemukan Barang
             │                       │
             ↓                       ↓
        Cari Barang             Laporkan Temuan
             │                       │
             ↓                       ↓
      Temukan Kandidat          Isi Informasi
             │                       │
             ↓                       ↓
        Ajukan Klaim            Pilih Satpam
             │                       │
             ↓                       ↓
         Verifikasi              Titip Barang
             │                       │
             ↓                       │
       Pickup Code ◄────────────────┘
             │
             ↓
       Datang ke Satpam
             │
             ↓
       Barang Dikembalikan
```

---

# 42. Design Success Criteria

Design dianggap berhasil jika pengguna dapat:

### Mahasiswa Penemu

```text
Membuka aplikasi
→ Melaporkan barang
→ Menentukan lokasi penitipan
→ Mendapat konfirmasi
```

tanpa kebingungan.

### Mahasiswa Pemilik

```text
Membuka aplikasi
→ Mencari barang
→ Menemukan kandidat
→ Mengajukan klaim
→ Melakukan verifikasi
→ Mendapat Pickup Code
```

dengan alur yang jelas.

### Satpam

Tidak membutuhkan proses digital yang kompleks.

Satpam cukup:

```text
Terima Barang
→ Simpan Barang
→ Verifikasi Pickup Code
→ Serahkan Barang
```

---

# 43. Design Philosophy

KETEMU PENS harus terasa seperti:

> "Aplikasi yang langsung ngerti apa yang mau saya lakukan."

Bukan:

> "Sistem administrasi kampus yang kebetulan punya fitur Lost & Found."

Setiap keputusan desain harus kembali pada pertanyaan:

> **Apakah desain ini membuat mahasiswa lebih mudah menemukan kembali barangnya?**

Jika jawabannya tidak, desain tersebut perlu disederhanakan atau dihilangkan.