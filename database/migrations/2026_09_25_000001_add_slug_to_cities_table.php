<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
            $table->index('slug');
        });

        // Backfill — generate slug from name, handle collisions with -2, -3
        $cities = DB::table('cities')->orderBy('id')->get(['id', 'name']);
        $usedSlugs = [];

        foreach ($cities as $city) {
            $base = Str::slug($city->name) ?: 'city-' . $city->id;
            $slug = $base;
            $i = 1;

            while (in_array($slug, $usedSlugs, true)) {
                $slug = $base . '-' . (++$i);
            }

            $usedSlugs[] = $slug;

            DB::table('cities')
                ->where('id', $city->id)
                ->update(['slug' => $slug]);
        }

        // After backfill, tighten to non-null + unique
        Schema::table('cities', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropIndex(['slug']);
            $table->dropColumn('slug');
        });
    }
};