<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_hour_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();

            // The specific date this override applies to
            $table->date('date');

            // For now, overrides are always "closed". Future-proofed with a boolean
            // so we can add "special hours" later without a schema change.
            $table->boolean('is_closed')->default(true);

            // Human-readable note ("Christmas Day", "Staff retreat")
            $table->string('note')->nullable();

            // Who created it (usually the owner)
            $table->foreignId('created_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->timestamps();

            // One override per branch per day
            $table->unique(['branch_id', 'date']);

            // Fast lookup: "does this branch have an override today?"
            $table->index(['branch_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_hour_overrides');
    }
};