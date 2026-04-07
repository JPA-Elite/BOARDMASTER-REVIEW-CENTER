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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('quote');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $reviewers = [
            [
                'name' => 'Patrick Bat-Og',
                'school' => 'Asia Pacific College',
                'email' => 'patrick.bat-og@boardmasters.ph',
                'quote' =>
                    'BoardMasters is the biggest game changer. It stopped me from spending too much time on topics I already mastered.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Sophie Dizon',
                'school' => 'Wesleyan University',
                'email' => 'sophie.dizon@boardmasters.ph',
                'quote' => 'The step-by-step explanations made the lessons stick. It finally clicked for me.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Matt Singson',
                'school' => 'Ateneo de Naga',
                'email' => 'matt.singson@boardmasters.ph',
                'quote' => 'Everything is organized in one app. I can practice on the bus and still track my progress.',
                'sort_order' => 3,
            ],
        ];

        $now = now();
        $reviewRows = [];

        foreach ($reviewers as $reviewer) {
            $userId = DB::table('users')->insertGetId([
                'name' => $reviewer['name'],
                'email' => $reviewer['email'],
                'school' => $reviewer['school'],
                'password' => Hash::make(Str::random(20)),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $reviewRows[] = [
                'user_id' => $userId,
                'quote' => $reviewer['quote'],
                'is_active' => true,
                'sort_order' => $reviewer['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('reviews')->insert($reviewRows);
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
