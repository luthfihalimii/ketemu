<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HealthCheckCommand extends Command
{
    protected $signature = 'ketemupens:health';

    protected $description = 'Periksa kesiapan production: mailer, antrean, scheduler, storage, dan konfigurasi keamanan.';

    public function handle(): int
    {
        $failed = false;
        $isProd = app()->isProduction();

        // 1. APP_KEY harus ada (kunci HMAC pickup code + enkripsi PII).
        if (blank(config('app.key'))) {
            $this->error('APP_KEY kosong. Generate dengan php artisan key:generate.');
            $failed = true;
        } else {
            $this->info('APP_KEY terpasang.');
        }

        // 2. Mailer: verifikasi email + notifikasi butuh SMTP sungguhan di prod.
        $mailer = (string) config('mail.default');
        if ($isProd && in_array($mailer, ['log', 'array'], true)) {
            $this->error("MAIL_MAILER={$mailer} di production: email verifikasi tidak akan sampai. Arahkan ke SMTP sungguhan.");
            $failed = true;
        } else {
            $this->info("Mailer: {$mailer}.");
        }

        // 3. Antrean: notifikasi berjalan afterCommit + ShouldQueue.
        $queue = (string) config('queue.default');
        $this->info("Queue connection: {$queue}. Pastikan `php artisan queue:work` berjalan di production.");
        try {
            $pending = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();
            $this->info("Jobs tertunda: {$pending}. Jobs gagal: {$failedJobs}.");
            if ($failedJobs > 0) {
                $this->warn("Ada {$failedJobs} failed jobs. Periksa dengan `php artisan queue:failed`.");
            }
        } catch (\Throwable $e) {
            $this->warn('Tabel jobs/failed_jobs belum termigrasi: '.$e->getMessage());
        }

        // 4. Scheduler: expire + purge-pii harus jalan tiap jam/hari via cron.
        $this->info('Scheduler harus aktif: `* * * * * php artisan schedule:run` (ketemupens:expire hourly, ketemupens:purge-pii daily).');

        // 5. Storage foto: lokal butuh symlink, R2 butuh env lengkap.
        $photosDisk = (string) config('ketemupens.photos.disk', 'public');
        if ($photosDisk === 'r2') {
            $missing = collect(['R2_ACCESS_KEY_ID' => config('filesystems.disks.r2.key'), 'R2_SECRET_ACCESS_KEY' => config('filesystems.disks.r2.secret'), 'R2_BUCKET' => config('filesystems.disks.r2.bucket'), 'R2_ENDPOINT' => config('filesystems.disks.r2.endpoint'), 'R2_PUBLIC_URL' => config('filesystems.disks.r2.url')])
                ->filter(fn ($v) => blank($v))->keys()->all();
            if ($missing !== []) {
                $this->error('R2 belum lengkap, kosong: '.implode(', ', $missing).'.');
                $failed = true;
            } else {
                $this->info('Storage foto: R2 ('.config('filesystems.disks.r2.url').').');
            }
        } elseif (! file_exists(public_path('storage')) && ! Storage::disk('public')->exists('.gitignore')) {
            $this->warn('public/storage belum ter-link. Jalankan `php artisan storage:link`.');
        } else {
            $this->info('Storage foto: lokal public.');
        }

        // 6. Session aman.
        if ($isProd && config('session.secure') !== true) {
            $this->warn('SESSION_SECURE_COOKIE sebaiknya true di production (HTTPS).');
        }

        // 7. Telegram opsional.
        if (blank(config('services.telegram.bot_token'))) {
            $this->info('Telegram belum dikonfigurasi (opsional, notif in-app tetap jalan).');
        } else {
            $this->info('Telegram terkonfigurasi.');
        }

        if ($failed) {
            $this->error('Health check GAGAL. Perbaiki item di atas sebelum launch.');

            return self::FAILURE;
        }

        $this->info('Health check LULUS.');

        return self::SUCCESS;
    }
}
