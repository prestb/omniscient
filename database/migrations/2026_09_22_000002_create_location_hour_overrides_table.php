<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
                Schema::create('location_hour_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();

            // The specific date this override applies to
            $table->date('date');

            // Closed-by-default; `is_special_hours` entries carry times.
            $table->boolean('is_closed')->default(true);

            // Human-readable note ("Christmas Day", "Staff retreat")
            $table->string('note')->nullable();

            // Who created it (usually the owner)
            $table->foreignId('created_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->timestamps();

            // One override per location per day
            $table->unique(['location_id', 'date']);

            // Fast lookup: "does this location have an override today?"
            $table->index(['location_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_hour_overrides');
    }
};