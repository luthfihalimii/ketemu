<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // Moderation trail: who flagged it, why, and who last acted on it.
            $table->timestamp('flagged_at')->nullable()->after('claim_attempts');
            $table->string('flag_reason')->nullable()->after('flagged_at');
            $table->text('moderation_note')->nullable()->after('flag_reason');
            $table->foreignId('moderated_by')->nullable()->after('moderation_note')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable()->after('moderated_by');

            $table->index('flagged_at');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('moderated_by');
            $table->dropColumn(['flagged_at', 'flag_reason', 'moderation_note', 'moderated_at']);
        });
    }
};
