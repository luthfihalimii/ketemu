# Deploy Production — KETEMU PENS

Checklist sebelum launch. Semua perintah dijalankan di server production.

## 1. Environment

```bash
cp .env.example .env
php artisan key:generate
```

Wajib di production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ketemupens.pens.ac.id
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
TRUSTED_PROXIES=127.0.0.1
```

## 2. Email (wajib — verifikasi akun bergantung padanya)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.pens.ac.id
MAIL_PORT=587
MAIL_USERNAME=noreply@pens.ac.id
MAIL_PASSWORD=<isi-di-server>
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@pens.ac.id
```

Uji:

```bash
php artisan ketemupens:test-mail admin@pens.ac.id
php artisan ketemupens:health
```

Selama `MAIL_MAILER=log`, admin melihat banner peringatan dan
`ActivityNotification` hanya masuk in-app (channel `mail` tidak didaftarkan).

## 3. Database + storage foto (R2)

```bash
php artisan migrate --force
php artisan storage:link   # hanya bila FILESYSTEM_PHOTOS_DISK=public
```

R2 (disarankan):

```env
FILESYSTEM_PHOTOS_DISK=r2
R2_ACCESS_KEY_ID=
R2_SECRET_ACCESS_KEY=
R2_BUCKET=ketemupens-photos
R2_ENDPOINT=https://<account>.r2.cloudflarestorage.com
R2_PUBLIC_URL=https://foto.ketemupens.id
```

Sync foto lama sekali:

```bash
aws s3 sync storage/app/public/items s3://$R2_BUCKET/items --endpoint-url $R2_ENDPOINT
```

## 4. Worker antrean (wajib — notifikasi queue)

Notifikasi memakai `ShouldQueue + afterCommit`. Tanpa worker, notifikasi
tidak terkirim tapi transaksi bisnis tetap jalan.

systemd `/etc/systemd/system/ketemupens-queue.service`:

```ini
[Unit]
Description=KETEMU PENS queue worker
After=network.target

[Service]
User=www-data
WorkingDirectory=/var/www/ketemupens
ExecStart=/usr/bin/php artisan queue:work --tries=3 --max-time=3600
Restart=always

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable --now ketemupens-queue
```

## 5. Scheduler (wajib — expire + purge PII)

```cron
* * * * * cd /var/www/ketemupens && php artisan schedule:run >> /dev/null 2>&1
```

Menjalankan: `ketemupens:expire` tiap jam, `ketemupens:purge-pii` harian.

## 6. Web server (contoh nginx)

- Terminate TLS di reverse proxy, forward `X-Forwarded-*` (lihat `TRUSTED_PROXIES`).
- `client_max_body_size 6M;` (foto max 5 MB).
- Cache lama untuk foto R2 via `R2_PUBLIC_URL` (immutable, nama acak).

## 7. Backup

- Database harian (mysqldump + retensi 7 hari, uji restore bulanan).
- R2 versioning aktif untuk `ketemupens-photos`.
- `storage/app/private` (arsip moderasi) ikut backup.

## 8. Setelah deploy

```bash
php artisan ketemupens:health
php artisan ketemupens:make-staff satpam.d4@pens.ac.id --role=guard
php artisan ketemupens:make-staff admin@pens.ac.id --role=admin
```
