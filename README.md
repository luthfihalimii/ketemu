# KETEMU PENS

Platform Lost & Found kampus untuk membantu mahasiswa PENS menemukan kembali barang yang hilang.

> Kembali Temukan Barangmu di PENS

Barang fisik tetap dititipkan ke satpam. Aplikasi ini mengelola informasi, pencarian, klaim, verifikasi, dan kode pengambilan. Lihat `AGENTS.md` untuk konteks produk lengkap.

## Alur utama

```
Mahasiswa menemukan barang -> lapor lewat KETEMU PENS -> serahkan ke satpam
-> konfirmasi penitipan -> barang tersedia di katalog -> pemilik mengajukan klaim
-> verifikasi ciri kepemilikan -> kode pengambilan diterbitkan
-> satpam catat identitas penerima -> barang diserahkan
```

## Serah-terima di pos satpam

Verifikasi kepemilikan terjadi **secara digital**, sebelum mahasiswa datang ke pos: pertanyaan ciri
barang dijawab lewat web dan kode pengambilan terbit otomatis bila cocok. Satpam tidak menilai
kepemilikan.

Karena kode bisa diteruskan ke orang lain, satpam **wajib mencatat nomor identitas (KTM/KTP) dan nama
penerima** saat menyerahkan barang. Nomor identitas disimpan di `pickup_codes.recipient_id_number`,
nama di `recipient_name`, dan keduanya masuk audit log `pickup.completed`. Tanpa keduanya, kode tidak
bisa diuangkan.

Catatan: nomor identitas adalah data pribadi. Kolom `recipient_id_number` dan
`recipient_name` disimpan **terenkripsi at-rest** dan dihapus otomatis oleh
`ketemupens:purge-pii` (harian) setelah serah-terima melampaui masa retensi
(default 90 hari, atur lewat `PII_RETENTION_DAYS`). Audit log `pickup.completed`
hanya menyimpan nomor identitas dalam bentuk tersamar (4 digit terakhir).

## Laporan hilang dan barang temuan

Laporan barang hilang dan laporan barang temuan adalah dua hal berbeda, dan sistem memperlakukannya
begitu:

- **Laporan hilang** (`REPORTED`) tidak pernah masuk katalog publik dan tidak pernah bisa diklaim.
  Barangnya tidak ada yang memegang.
- **Laporan temuan** (`WAITING_DEPOSIT` ke atas) yang muncul di katalog dan bisa diklaim.

Pemilik dapat menautkan laporan hilangnya ke barang temuan yang cocok lewat halaman
**Riwayat → Cari kecocokan** (`items.matched_item_id`). Tautan ini hanya mencatat bahwa kedua laporan
menunjuk barang yang sama; **tautan tidak memberi hak mengambil**. Pengambilan tetap memerlukan
jawaban verifikasi dari penemu. Saat barang temuan diserahkan, laporan hilang yang tertaut ikut
selesai (`RETURNED`).

Karena jawaban verifikasi ditulis oleh penemu, laporan hilang tidak boleh menjadi dasar verifikasi
kepemilikan: pemilik yang menulis jawabannya sendiri akan selalu "lulus".

## Menjalankan

Butuh PHP 8.3+, Composer, Node.js, dan MySQL.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev
php artisan serve
```

Untuk development tanpa MySQL, ubah `DB_CONNECTION=sqlite` di `.env`.

## Keamanan kredensial

- `.env` **tidak pernah** boleh masuk git (CI memeriksa ini). Kredensial produksi
  (password DB, API key) hanya boleh berada di server, bukan di `.env` mesin developer.
- Jika kredensial pernah terekspos, lakukan rotasi. Perhatian: merotasi `APP_KEY`
  meng-invalidate semua `code_encrypted` kode pengambilan yang masih aktif — admin
  harus menerbitkan ulang kode untuk klaim yang menunggu pengambilan
  (**Moderasi Klaim → Terbitkan kode baru**), dan menjalankan
  `php artisan ketemupens:encrypt-pii` untuk data penerima lama.
- `SESSION_ENCRYPT=true` wajib di production agar isi session terenkripsi.

## Email kampus & verifikasi

Registrasi publik hanya menerima alamat email kampus PENS
(pola di `config/ketemupens.php` → `auth.allowed_email_patterns`):

- `*@*.student.pens.ac.id` — mahasiswa semua prodi (mis. `@tif.student.pens.ac.id`)
- `*@pens.ac.id` — dosen/staff

Setelah mendaftar, pengguna **wajib memverifikasi email** sebelum bisa melapor
atau mengklaim. Dasbor dan notifikasi tetap bisa diakses agar tidak deadlock.

**Prasyarat deployment:** `MAIL_MAILER` harus diarahkan ke SMTP sungguhan agar
email verifikasi benar-benar terkirim. Selama mailer masih `log`, channel `mail`
pada notifikasi tidak didaftarkan (lihat `ActivityNotification::via()`).

## Antrian notifikasi

Notifikasi (`ActivityNotification`) dijalankan lewat antrian (`ShouldQueue` +
`afterCommit`), sehingga pengiriman email/Telegram tidak pernah menahan atau
me-rollback transaksi bisnis. Di production ini berarti **worker antrian wajib
berjalan** — tanpa worker, notifikasi tidak akan terkirim:

```bash
php artisan queue:work
```

Di development, `QUEUE_CONNECTION=sync` tetap aman (notifikasi terkirim langsung).

### Akun demo

`php artisan migrate --seed` membuat akun demo (hanya di environment non-production) dengan password acak yang dicetak ke terminal, atau gunakan `DEMO_PASSWORD` untuk menentukan sendiri:

| Peran | Email |
| --- | --- |
| Admin | `admin@ketemupens.test` |
| Satpam | `satpam@ketemupens.test` |
| Mahasiswa | `mahasiswa@ketemupens.test` |
| Mahasiswa | `andi@ketemupens.test` |

### Akun satpam & admin di production

`DemoUserSeeder` sengaja tidak jalan di production, dan registrasi publik hanya membuat akun mahasiswa. Akun petugas dibuat lewat CLI:

```bash
php artisan ketemupens:make-staff satpam.d4@pens.ac.id --role=guard --name="Satpam Pos D4"
php artisan ketemupens:make-staff admin@pens.ac.id --role=admin
```

Tanpa `--password`, password diminta lewat prompt dan tidak muncul di riwayat shell. Command menolak email yang sudah terdaftar dan mencatat pembuatan akun ke audit log.

## Expiry otomatis

Barang temuan yang lama tidak diambil dan kode pengambilan yang habis masa berlaku ditandai otomatis oleh scheduler:

```bash
php artisan ketemupens:expire
```

Scheduler yang sama menandai laporan temuan yang **belum dikonfirmasi dititipkan** setelah `ITEM_DEPOSIT_REMINDER_AFTER_DAYS` hari (default 2). Laporan itu **tidak ditutup** — barangnya mungkin sudah ada di pos satpam, jadi menutupnya akan membuang satu-satunya petunjuk yang bisa mempertemukannya dengan pemilik. Yang terjadi:

- `items.deposit_reminded_at` diisi (sekali saja, agar audit log tidak terisi berulang tiap jam).
- Penemu melihat panel "Perlu ditindaklanjuti" di halaman Riwayat.
- Admin melihat antrean **Moderasi → Belum Dititipkan**.

Scheduler yang sama juga **melepaskan klaim yang ditinggal**: klaim tersetujui
yang kode pengambilannya kedaluwarsa lebih dari `PICKUP_CODE_RELEASE_GRACE_DAYS`
hari (default 3) dibatalkan otomatis, barang kembali `STORED`, dan pemilik
dinotifikasi untuk meminta kode baru via admin.

Di production, jalankan `php artisan schedule:run` tiap menit lewat cron. Durasi diatur lewat `ITEM_STALE_AFTER_DAYS`, `ITEM_DEPOSIT_REMINDER_AFTER_DAYS`, `PICKUP_CODE_VALIDITY_MINUTES`, dan `PICKUP_CODE_RELEASE_GRACE_DAYS`.

## Notifikasi

Dua saluran, keduanya aktif:

| Saluran | Penerima | Syarat |
|---|---|---|
| **In-app** (lonceng di navbar) | Semua pengguna | Tidak ada |
| **Telegram** | Mahasiswa yang menghubungkan akunnya | `TELEGRAM_BOT_TOKEN` diisi |

Kejadian yang memicu notifikasi: klaim disetujui, jawaban verifikasi salah, klaim ditolak admin, laporan dinonaktifkan admin, penitipan belum dikonfirmasi, dan barang tertaut sudah kembali.

Admin menerima tiga pemberitahuan: laporan ditandai mencurigakan, penitipan belum dikonfirmasi lewat batas waktu, dan klaim yang kehabisan percobaan verifikasi.

### Mengaktifkan Telegram

Telegram dipilih sebagai pengganti email karena gratis dan lebih mungkin dibaca mahasiswa. Konsekuensinya: Telegram **tidak bisa mengirim ke email atau nomor HP** — setiap mahasiswa harus menghubungkan akunnya sendiri lewat **Pengaturan → Notifikasi Telegram**. Yang tidak menghubungkan tetap mendapat notifikasi in-app saja.

1. Buat bot lewat [@BotFather](https://t.me/BotFather), salin tokennya.
2. Isi `.env`:
   ```env
   TELEGRAM_BOT_TOKEN=123456:ABC...
   TELEGRAM_BOT_USERNAME=nama_bot_kamu
   TELEGRAM_WEBHOOK_SECRET=string-acak-panjang
   ```
3. Daftarkan webhook ke server:
   ```bash
   curl -X POST "https://api.telegram.org/bot<TOKEN>/setWebhook" \
     -d "url=https://domain-kamu/telegram/webhook" \
     -d "secret_token=<TELEGRAM_WEBHOOK_SECRET>"
   ```

Webhook berada di luar middleware sesi karena Telegram tidak punya sesi; ia diautentikasi lewat header rahasia yang dibandingkan dengan `hash_equals`. Tanpa `TELEGRAM_WEBHOOK_SECRET`, endpoint mengembalikan 403.

**Email belum aktif, dan ini disengaja.** `MAIL_MAILER` masih `log`, jadi email hanya masuk `storage/logs/laravel.log`. Channel `mail` sengaja tidak didaftarkan di `ActivityNotification::via()` supaya tidak terlihat seperti sudah berjalan.

## Testing

```bash
php artisan test
```

CI menjalankan suite yang sama pada setiap push dan pull request (`.github/workflows/tests.yml`).
