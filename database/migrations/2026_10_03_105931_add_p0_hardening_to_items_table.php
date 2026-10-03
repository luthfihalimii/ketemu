<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * P0 hardening: catatan internal non-publik + konfirmasi penitipan 2-pihak.
     *
     * - private_note: detail tambahan yang TIDAK tampil ke publik, hanya
     *   pelapor, satpam, dan admin.
     * - deposit_requested_at: kapan penemu mengklaim sudah menitipkan.
     * - deposit_confirmed_at/by: kapan satpam memverifikasi fisik barang.
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->text('private_note')->nullable()->after('description');
            $table->timestamp('deposit_requested_at')->nullable()->after('deposit_reminded_at');
            $table->timestamp('deposit_confirmed_at')->nullable()->after('deposit_requested_at');
            $table->foreignId('deposit_confirmed_by')->nullable()->after('deposit_confirmed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('deposit_confirmed_by');
            $table->dropColumn(['private_note', 'deposit_requested_at', 'deposit_confirmed_at']);
        });
    }
};
