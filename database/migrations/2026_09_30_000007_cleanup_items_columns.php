<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // claim_attempts tidak pernah dibaca; sisa percobaan verifikasi
            // dilacak per-klaim di claims.attempt_count.
            $table->dropColumn('claim_attempts');

            // Laporan hilang memang tidak punya jawaban verifikasi (jawaban
            // ditulis penemu, bukan pemilik), jadi kolom ini wajib nullable.
            $table->text('verification_answer')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->unsignedTinyInteger('claim_attempts')->default(0)->after('status');
            $table->text('verification_answer')->nullable(false)->change();
        });
    }
};
