<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_codes', function (Blueprint $table) {
            $table->id();
            // Not unique: an expired or cancelled code is superseded by a new one.
            $table->foreignId('claim_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Only a fingerprint of the code is persisted in clear text lookup form;
            // the recoverable copy is encrypted at rest with the app key.
            $table->string('code_hash');
            $table->text('code_encrypted')->nullable();
            $table->string('code_hint', 8);
            $table->string('status')->default('ACTIVE')->index();

            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            // At most one usable code per claim is enforced in the service.
            $table->index(['claim_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_codes');
    }
};
