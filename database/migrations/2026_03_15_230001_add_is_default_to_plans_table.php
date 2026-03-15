<?php

use App\Models\Plan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('description');
        });

        $freePlan = DB::table('plans')
            ->whereRaw('LOWER(name) LIKE ?', ['%free%'])
            ->orderBy('id')
            ->first();

        if (! $freePlan) {
            $freePlan = DB::table('plans')
                ->orderBy('price')
                ->orderBy('id')
                ->first();
        }

        if (! $freePlan) {
            DB::table('plans')->insert([
                'name' => 'Free Plan',
                'price' => 0,
                'billing_cycle' => Plan::BILLING_MONTHLY,
                'description' => 'Default free access plan.',
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return;
        }

        DB::table('plans')->where('id', $freePlan->id)->update([
            'name' => 'Free Plan',
            'price' => 0,
            'billing_cycle' => Plan::BILLING_MONTHLY,
            'is_default' => true,
            'updated_at' => now(),
        ]);

        DB::table('plans')
            ->where('id', '!=', $freePlan->id)
            ->where('is_default', true)
            ->update([
                'is_default' => false,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });
    }
};
