<?php

return [

    'trusted_proxies' => array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', ''))))),

    /*
    |--------------------------------------------------------------------------
    | Domain rules
    |--------------------------------------------------------------------------
    |
    | Tunable business parameters. Kept in config (not hard-coded) so the
    | campus can adjust policy without a code change.
    |
    */

    'auth' => [
        // Registrasi publik hanya untuk alamat email kampus PENS: subdomain
        // mahasiswa (per prodi, mis. @tif.student.pens.ac.id) dan domain utama
        // @pens.ac.id untuk dosen/staff.
        'allowed_email_patterns' => [
            '/@([a-z0-9-]+\.)*student\.pens\.ac\.id$/i',
            '/@pens\.ac\.id$/i',
        ],
    ],

    'expiry' => [
        // How long a found item may sit on the shelf before it is expired
        // automatically. Unclaimed items are usually handed to the campus
        // warehouse after this window.
        'stale_after_days' => (int) env('ITEM_STALE_AFTER_DAYS', 60),

        // How long a finder has to confirm the handover to security staff
        // before the report is flagged as needing follow-up. The report is
        // never closed automatically: the item may already be at the post.
        'deposit_reminder_after_days' => (int) env('ITEM_DEPOSIT_REMINDER_AFTER_DAYS', 2),
    ],

    'pickup_code' => [
        // How long a student has to collect an item once a code is issued.
        'validity_minutes' => (int) env('PICKUP_CODE_VALIDITY_MINUTES', 60 * 24 * 3),

        // Klaim disetujui yang kodenya dibiarkan kedaluwarsa selama ini
        // dilepas otomatis sehingga barang kembali tersedia.
        'release_grace_days' => (int) env('PICKUP_CODE_RELEASE_GRACE_DAYS', 3),
    ],

    'demo' => [
        // Password akun demo (hanya environment non-production). Kosongkan
        // agar password acak dibuat dan dicetak ke terminal saat seeding.
        'password' => env('DEMO_PASSWORD'),
    ],

    'pii' => [
        // Nomor identitas & nama penerima dikosongkan setelah serah-terima
        // melampaui masa retensi ini. Audit log menyimpan bentuk tersamar.
        'retention_days' => (int) env('PII_RETENTION_DAYS', 90),
    ],

    'photos' => [
        // Disk foto barang: 'public' (lokal, default dev) atau 'r2'
        // (Cloudflare R2 public bucket, disarankan production).
        // photo_path di DB tidak menyimpan nama disk, jadi ganti disk tidak
        // perlu migrasi data — cukup sync file lama ke bucket sekali.
        'disk' => env('FILESYSTEM_PHOTOS_DISK', 'public'),
    ],

    'hold' => [
        // Penemu boleh menahan barang sebelum titip ke satpam. Foto wajib +
        // janji wajib saat menahan; lewat tenggat = overdue + admin follow-up.
        'max_hours' => (int) env('HOLD_MAX_HOURS', 24),
    ],

];
