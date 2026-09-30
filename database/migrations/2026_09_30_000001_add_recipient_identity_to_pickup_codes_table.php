<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pickup_codes', function (Blueprint $table) {
            // The pickup code proves the right to collect, not the identity of
            // whoever is holding it. The guard records the identity document
            // they physically inspected, so a handover always has an owner.
            // Nullable: codes issued before this column existed have no record.
            $table->string('recipient_id_number', 40)->nullable()->after('verified_at');
            $table->string('recipient_name', 100)->nullable()->after('recipient_id_number');
        });
    }

    public function down(): void
    {
        Schema::table('pickup_codes', function (Blueprint $table) {
            $table->dropColumn(['recipient_id_number', 'recipient_name']);
        });
    }
};
