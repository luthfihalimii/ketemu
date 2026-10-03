# AI Matching (P2) — heuristik lokal

Sesuai `AGENTS.md §19`: AI hanya memberi **saran berperingkat**, tidak pernah
menentukan kepemilikan. Alur tetap:

```
saran skor → pemilik menautkan → klaim → jawaban verifikasi penemu
→ kode pickup → satpam serah terima
```

## Cara kerja sekarang (`ItemMatchService`)

Tanpa LLM / API eksternal, deterministik dan bisa di-test:

- Teks publik saja (`title + description + color + brand`). `verification_answer`
  dan `private_note` TIDAK PERNAH dipakai — kalau dipakai, pemilik laporan
  hilang yang menulis deskripsinya sendiri selalu "lulus".
- Tokenisasi Indonesia ringan: lowercase, token ≥3 huruf, buang stopwords
  kampus (`gedung`, `lantai`, `pos`, ...), cosine similarity TF.
- Skor 0–100: teks 50 + kategori sama 25 + lokasi sama 15 + waktu ±7 hari 10
  (±30 hari 5). Alasan ditampilkan (`Kategori sama · Waktu berdekatan`).

## Upgrade path (tanpa ubah kontrak)

`ItemService::candidateFoundItems()` mendelegasikan ke
`ItemMatchService::suggestFor()`. Untuk naik ke embedding:

1. Tambah kolom `embedding` (pgvector) / index Meilisearch.
2. Isi via queue job saat laporan dibuat/diubah.
3. Ganti isi `cosine()` dengan cosine embedding; bobot + alasan tetap sama.
4. Tambah evaluasi: precision@6 di `tests/Feature` sebelum ganti.

## Batasan yang disengaja

- Kandidat dibatasi 60 terbaru lalu diperingkat — bukan scan seluruh tabel.
- Tidak ada auto-claim / auto-approve dari skor berapa pun.
- Foto belum dipakai (butuh CLIP + biaya). Teks + atribut cukup untuk P2 awal.
