<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Model "tahan dulu + SLA": penemu boleh pegang barang maks HOLD_MAX_HOURS
     * (default 24 jam) dengan janji + foto wajib, sebelum wajib titip ke satpam.
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->timestamp('hold_until')->nullable()->after('deposit_confirmed_by');
            $table->boolean('hold_promised')->default(false)->after('hold_until');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['hold_until', 'hold_promised']);
        });
    }
};
