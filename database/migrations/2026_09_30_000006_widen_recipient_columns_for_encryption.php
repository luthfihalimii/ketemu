<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ciphertext is several times longer than plaintext, so the recipient
        // identity columns must hold text before the encrypted cast is used.
        Schema::table('pickup_codes', function (Blueprint $table) {
            $table->text('recipient_id_number')->nullable()->change();
            $table->text('recipient_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('pickup_codes', function (Blueprint $table) {
            $table->string('recipient_id_number', 40)->nullable()->change();
            $table->string('recipient_name', 100)->nullable()->change();
        });
    }
};
