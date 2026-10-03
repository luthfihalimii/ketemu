# Audit Alur — Penyederhanaan Pertemuan Fisik

Dihitung sebagai interaksi sadar (klik/scan/tampil), bukan keystroke.
Tujuan: verifikasi tetap ketat, pertemuan fisik minimal.

## Titip (penemu + satpam)

Sebelum (2 sesi terpisah, ~7 interaksi):
1. Penemu: buka form → isi + submit (2)
2. Penemu: buka detail → klik "Sudah dititipkan" (2)
3. Satpam (nanti): buka verifikasi → cari di daftar pending → klik konfirmasi (3)

Sesudah (1 pertemuan di pos):
1. Penemu: buka form → isi + submit (2)
2. Penemu: tunjukkan QR titip di HP (0 klik)
3. Satpam: scan QR / ketik `KP-...` → Konfirmasi 1 langkah (1–2)

Fallback bila satpam tidak di dekatnya: tombol "Saya sudah titipkan
(konfirmasi mandiri)" tetap ada, lalu satpam susulkan konfirmasi dari
daftar pending. Barang `WAITING_DEPOSIT` tidak bisa diklaim sampai
`STORED`, jadi ghost tidak bocor ke katalog.

## Ambil (pemilik + satpam)

Tetap: klaim + jawab ciri (max 3x) → `password.confirm` → QR/kode →
satpam scan (0 ketik) + catat KTM → struk. QR ambil dan QR titip dibedakan
scanner: payload `KP-...` → kolom titip, 8-char → kolom ambil.

## Lapor hilang (pemilik)

Sebelum: lapor (2) → matches (1) → tautkan (1) → katalog (1) → klaim (3+).
Sesudah: tautan jadi **opsional** ("Lihat yang mirip (opsional)").
Jalur cepat: lapor (2) → katalog → klaim (3+). Tautan tidak memberi hak
ambil dan tidak pernah dipakai sebagai jawaban verifikasi.

## Yang TIDAK disederhanakan (sengaja)

- Jawaban verifikasi min 8 char + max 3 percobaan + hash.
- Kode 1x pakai + expiry + HMAC, halaman kode di balik `password.confirm`.
- Satpam wajib catat identitas penerima (mask di audit/struk, enkripsi at-rest).
