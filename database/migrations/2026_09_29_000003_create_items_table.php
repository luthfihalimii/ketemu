<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();

            // Identity
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();

            // Discovery / loss details
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('location_detail')->nullable();
            $table->dateTime('occurred_at')->nullable();

            // Physical attributes used for disambiguation (non-secret)
            $table->string('color')->nullable();
            $table->string('brand')->nullable();
            $table->string('photo_path')->nullable();

            // Deposit / handover plan
            $table->foreignId('deposit_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('deposit_note')->nullable();

            // Workflow
            $table->string('status')->default('REPORTED')->index();
            $table->unsignedTinyInteger('claim_attempts')->default(0);

            // Secret verification answer (hashed, never exposed)
            $table->text('verification_question')->nullable();
            $table->text('verification_answer');

            $table->timestamp('stored_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'category_id']);
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
