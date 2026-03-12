<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('school')->nullable()->after('email');
            $table->date('birthdate')->nullable()->after('school');
            $table->string('place')->nullable()->after('birthdate');
            $table->string('phone_number')->nullable()->after('place');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['school', 'birthdate', 'place', 'phone_number']);
        });
    }
};
