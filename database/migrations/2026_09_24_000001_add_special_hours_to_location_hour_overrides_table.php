<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
        public function up(): void
    {
        Schema::table('location_hour_overrides', function (Blueprint $table) {
            // ✅ Special hours — nullable. When is_closed = false and both are set,
            //    the override represents custom hours for the day instead of "closed".
            $table->time('opens_at')->nullable()->after('is_closed');
            $table->time('closes_at')->nullable()->after('opens_at');
        });
    }

    public function down(): void
    {
        Schema::table('location_hour_overrides', function (Blueprint $table) {
            $table->dropColumn(['opens_at', 'closes_at']);
        });
    }
};