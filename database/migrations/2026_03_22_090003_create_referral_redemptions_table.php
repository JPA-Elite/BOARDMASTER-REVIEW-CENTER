<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referral_offer_id')->constrained('referral_offers')->cascadeOnDelete();
            $table->unsignedInteger('points_spent')->default(0);
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->unsignedInteger('question_limit')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_redemptions');
    }
};
