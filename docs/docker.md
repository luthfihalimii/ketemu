# Docker — KETEMU PENS

Satu perintah untuk dev/staging yang identik: PHP 8.3-FPM + nginx + MySQL 8.4
+ worker antrean + scheduler. Tanpa systemd/cron manual.

## Prasyarat

Docker + Compose plugin, lalu siapkan env:

```bash
cp .env.example .env
php artisan key:generate   # atau: docker compose run --rm -e SKIP_MIGRATIONS=1 app php artisan key:generate
```

Wajib di `.env` untuk compose:

```env
DB_DATABASE=ketemupens
DB_USERNAME=ketemu
DB_PASSWORD=<acak>
DB_ROOT_PASSWORD=<acak-berbeda>
APP_PORT=8000
```

`DB_HOST` ditimpa jadi `db` oleh compose. `FILESYSTEM_PHOTOS_DISK=public`
tetap jalan (volume `storage`); untuk R2 isi variabel `R2_*` seperti biasa.

## Jalankan

```bash
docker compose up -d --build
docker compose logs -f app        # tunggu "migrate DONE"
docker compose exec app php artisan ketemupens:health
docker compose exec app php artisan migrate --seed   # demo, non-prod saja
```

Buka `http://localhost:8000`. Service yang jalan: `app` (php-fpm),
`web` (nginx :80 → host `${APP_PORT}`), `db`, `queue` (`queue:work`),
`scheduler` (`schedule:work` — pengganti cron `schedule:run`).

## Catatan

- Entrypoint otomatis: tunggu DB → `migrate --force` → `storage:link`.
  Set `SKIP_MIGRATIONS=1` untuk service `queue`/`scheduler` (sudah di-set).
- Upload max disamakan: nginx `client_max_body_size 6M` + validasi 5 MB.
- Jangan commit `.env`. Secret prod hanya di server / secret manager.
- Hentikan: `docker compose down` (data DB aman di volume `dbdata`).
