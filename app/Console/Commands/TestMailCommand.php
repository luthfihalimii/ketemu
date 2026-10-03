<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestMailCommand extends Command
{
    protected $signature = 'ketemupens:test-mail {email : Alamat tujuan uji}';

    protected $description = 'Kirim email uji untuk memastikan MAIL_MAILER production berfungsi sebelum launch.';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Alamat email tidak valid.');

            return self::FAILURE;
        }

        $mailer = (string) config('mail.default');
        if (in_array($mailer, ['log', 'array'], true) && app()->isProduction()) {
            $this->error("MAIL_MAILER={$mailer} di production: email tidak akan sampai. Arahkan ke SMTP dulu.");

            return self::FAILURE;
        }

        Mail::raw('Ini email uji KETEMU PENS. Jika kamu menerima ini, konfigurasi mailer sudah benar.', function ($message) use ($email): void {
            $message->to($email)->subject('[KETEMU PENS] Uji email');
        });

        $this->info("Email uji dikirim ke {$email} via mailer {$mailer}. Periksa inbox/spam.");

        return self::SUCCESS;
    }
}
