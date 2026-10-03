<?php

namespace App\Console\Commands;

use App\Models\PickupCode;
use Illuminate\Console\Command;

/**
 * Hapus PII penerima (nomor identitas & nama) dari kode pengambilan yang
 * sudah lama digunakan. Audit log tetap menyimpan bentuk tersamar, jadi
 * jejak serah-terima tidak hilang.
 */
class PurgeRecipientPii extends Command
{
    protected $signature = 'ketemupens:purge-pii {--days= : Masa retensi dalam hari (default dari config)}';

    protected $description = 'Kosongkan data identitas penerima pada kode pengambilan yang melampaui masa retensi.';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('ketemupens.pii.retention_days', 90));
        $cutoff = now()->subDays($days);

        // Bulk update; cast terenkripsi sengaja dilewati karena kolom dikosongkan.
        $purged = PickupCode::query()
            ->whereNotNull('used_at')
            ->where('used_at', '<=', $cutoff)
            ->where(function ($query) {
                $query->whereNotNull('recipient_id_number')
                    ->orWhereNotNull('recipient_name');
            })
            ->update([
                'recipient_id_number' => null,
                'recipient_name' => null,
            ]);

        $this->info("PII penerima dikosongkan pada {$purged} kode pengambilan (retensi {$days} hari).");

        return self::SUCCESS;
    }
}
