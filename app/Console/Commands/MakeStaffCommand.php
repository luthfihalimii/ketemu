<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\password as promptPassword;

class MakeStaffCommand extends Command
{
    protected $signature = 'ketemupens:make-staff
                            {email : Email akun petugas}
                            {--role=guard : Peran akun, "guard" atau "admin"}
                            {--name= : Nama petugas; bila kosong diturunkan dari email}
                            {--password= : Password akun; bila kosong akan diminta lewat prompt}';

    protected $description = 'Membuat akun satpam atau admin untuk operasional production.';

    public function handle(AuditLogger $audit): int
    {
        $email = (string) $this->argument('email');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error('Email tidak valid.');

            return self::FAILURE;
        }

        $role = Role::tryFrom((string) $this->option('role'));

        if (! in_array($role, [Role::Guard, Role::Admin], true)) {
            error('Peran harus "guard" atau "admin".');

            return self::FAILURE;
        }

        // Never silently overwrite an existing account's password or role.
        if (User::query()->where('email', $email)->exists()) {
            error("Email {$email} sudah terdaftar. Hapus atau ubah akun tersebut secara manual.");

            return self::FAILURE;
        }

        $password = $this->resolvePassword();

        if ($password === null) {
            return self::FAILURE;
        }

        $user = new User([
            'name' => (string) ($this->option('name') ?: $this->nameFromEmail($email)),
            'email' => $email,
            'password' => $password,
            'role' => $role,
        ]);

        // email_verified_at is deliberately not mass-assignable, so it is set
        // directly. Staff accounts are provisioned by an operator, never
        // self-registered, so there is no mailbox to verify against.
        $user->email_verified_at = now();
        $user->save();

        $audit->log(
            event: 'auth.staff_created',
            description: "Akun {$role->label()} dibuat melalui CLI.",
            auditable: $user,
            properties: ['role' => $role->value],
        );

        info("Akun {$role->label()} {$email} berhasil dibuat.");

        return self::SUCCESS;
    }

    /**
     * Derive a readable display name from an email local part.
     */
    private function nameFromEmail(string $email): string
    {
        return Str::headline(str_replace(['.', '_', '-'], ' ', Str::before($email, '@')));
    }

    /**
     * Resolve the password from the option, or prompt for it interactively.
     */
    private function resolvePassword(): ?string
    {
        $password = (string) $this->option('password');

        if ($password !== '') {
            return $this->validatePassword($password);
        }

        if (! $this->input->isInteractive()) {
            error('Sertakan --password saat dijalankan non-interaktif.');

            return null;
        }

        $password = promptPassword(label: 'Password', required: true);
        $confirmation = promptPassword(label: 'Konfirmasi password', required: true);

        if ($password !== $confirmation) {
            error('Konfirmasi password tidak sama.');

            return null;
        }

        return $this->validatePassword($password);
    }

    private function validatePassword(string $password): ?string
    {
        $validator = Validator::make(
            ['password' => $password],
            ['password' => [Password::min(8)->letters()->numbers()]],
        );

        if ($validator->fails()) {
            error((string) $validator->errors()->first('password'));

            return null;
        }

        return $password;
    }
}
