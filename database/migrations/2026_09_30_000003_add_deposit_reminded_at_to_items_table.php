<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // A found report whose finder never confirmed handing the item to
            // security staff. This is a reminder marker, not a resolution: the
            // report keeps WAITING_DEPOSIT so the item is never lost to a
            // timeout when it may well be sitting at the security post.
            $table->timestamp('deposit_reminded_at')->nullable()->after('matched_item_id');

            // The queue is scanned on every admin page load, so index the
            // column the "perlu ditindaklanjuti" filter reads.
            $table->index(['status', 'deposit_reminded_at']);
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['status', 'deposit_reminded_at']);
            $table->dropColumn('deposit_reminded_at');
        });
    }
};
