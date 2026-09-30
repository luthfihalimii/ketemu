<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // A lost report points at the found item that turned out to be the
            // same belonging. The link is the single source of truth for
            // "matched": no separate status flag that could drift from it.
            $table->foreignId('matched_item_id')
                ->nullable()
                ->after('deposit_note')
                ->constrained('items')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('matched_item_id');
        });
    }
};
