<?php

namespace App\Console\Commands;

use App\Models\PickupCode;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Migrasi satu kali: re-encrypt kolom recipient_* yang masih berisi plaintext
 * dari sebelum cast 'encrypted' dipasang. Baris yang sudah terenkripsi
 * dilewati, jadi command ini aman dijalankan ulang.
 */
class EncryptRecipientPii extends Command
{
    protected $signature = 'ketemupens:encrypt-pii';

    protected $description = 'Re-encrypt data identitas penerima lama yang masih tersimpan sebagai plaintext.';

    public function handle(): int
    {
        $migrated = 0;
        $skipped = 0;

        PickupCode::query()
            ->where(function ($query) {
                $query->whereNotNull('recipient_id_number')
                    ->orWhereNotNull('recipient_name');
            })
            ->chunkById(100, function ($codes) use (&$migrated, &$skipped) {
                foreach ($codes as $code) {
                    $updates = [];

                    foreach (['recipient_id_number', 'recipient_name'] as $column) {
                        $raw = $code->getRawOriginal($column);

                        if ($raw === null) {
                            continue;
                        }

                        // Sudah terenkripsi? Lewati.
                        try {
                            Crypt::decryptString($raw);

                            continue;
                        } catch (DecryptException) {
                            $updates[$column] = Crypt::encryptString($raw);
                        }
                    }

                    if ($updates === []) {
                        $skipped++;

                        continue;
                    }

                    $code->newQuery()->whereKey($code->getKey())->update($updates);
                    $migrated++;
                }
            });

        $this->info("Selesai. {$migrated} baris di-re-encrypt, {$skipped} baris sudah terenkripsi.");

        return self::SUCCESS;
    }
}
