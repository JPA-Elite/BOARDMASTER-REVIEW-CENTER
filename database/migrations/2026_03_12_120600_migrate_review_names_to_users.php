<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reviews')) {
            return;
        }

        if (Schema::hasColumn('reviews', 'reviewer_name')) {
            $reviews = DB::table('reviews')
                ->select('id', 'user_id', 'reviewer_name', 'reviewer_school')
                ->get();

            foreach ($reviews as $review) {
                if ($review->user_id) {
                    continue;
                }

                $emailBase = Str::slug($review->reviewer_name ?? 'reviewer', '.');
                $email = $emailBase.'+review'.$review->id.'@boardmasters.ph';

                $userId = DB::table('users')->insertGetId([
                    'name' => $review->reviewer_name ?? 'Reviewer',
                    'email' => $email,
                    'school' => $review->reviewer_school,
                    'password' => Hash::make(Str::random(20)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('reviews')
                    ->where('id', $review->id)
                    ->update(['user_id' => $userId]);
            }
        }

        Schema::table('reviews', function (Blueprint $table) {
            if (Schema::hasColumn('reviews', 'reviewer_name')) {
                $table->dropColumn(['reviewer_name', 'reviewer_school']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            if (! Schema::hasColumn('reviews', 'reviewer_name')) {
                $table->string('reviewer_name')->nullable();
                $table->string('reviewer_school')->nullable();
            }
        });
    }
};
