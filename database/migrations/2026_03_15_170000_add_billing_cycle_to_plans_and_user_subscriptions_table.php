<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('billing_cycle', 20)->default('monthly')->after('price');
        });

        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->string('billing_cycle', 20)->default('monthly')->after('plan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropColumn('billing_cycle');
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('billing_cycle');
        });
    }
};
