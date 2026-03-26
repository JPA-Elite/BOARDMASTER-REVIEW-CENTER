<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referral_redemptions', function (Blueprint $table) {
            $table->timestamp('expired_notified_at')->nullable()->after('expires_at');
            $table->index(
                ['expired_notified_at', 'expires_at'],
                'referral_redemptions_expired_notified_expires_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('referral_redemptions', function (Blueprint $table) {
            $table->dropIndex('referral_redemptions_expired_notified_expires_index');
            $table->dropColumn('expired_notified_at');
        });
    }
};

