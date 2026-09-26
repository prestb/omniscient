<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            // Area is optional — the "Area" field in the UI has no
            // required asterisk, and BranchController validation treats
            // it as nullable. Aligns the schema with the validation.
            $table->foreignId('area_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->foreignId('area_id')->nullable(false)->change();
        });
    }
};