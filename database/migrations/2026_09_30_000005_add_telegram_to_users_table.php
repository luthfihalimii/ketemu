<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Telegram has no way to address a phone number or an email, so a
            // student has to link their own account once. Until then there is
            // no external channel for them and they only see in-app notices.
            $table->string('telegram_chat_id')->nullable()->unique()->after('email_verified_at');

            // One-time secret handed out on the settings page. It is the only
            // thing that proves the person pressing /start controls this
            // account, so it is consumed on use.
            $table->string('telegram_link_token', 64)->nullable()->unique()->after('telegram_chat_id');
            $table->timestamp('telegram_link_token_expires_at')->nullable()->after('telegram_link_token');
            $table->timestamp('telegram_linked_at')->nullable()->after('telegram_link_token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'telegram_chat_id',
                'telegram_link_token',
                'telegram_link_token_expires_at',
                'telegram_linked_at',
            ]);
        });
    }
};
