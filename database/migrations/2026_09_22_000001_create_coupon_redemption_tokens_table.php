<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ✅ Forward-compatible: `scope` on coupons for future shop feature
        Schema::table('coupons', function (Blueprint $table) {
            $table->enum('scope', ['in_person', 'online', 'both'])
                ->default('in_person')
                ->after('is_active');
        });

        // ✅ Redemption tokens — one per "Redeem Coupon" click
        Schema::create('coupon_redemption_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // The opaque token in the QR URL
            $table->string('token', 64)->unique();

            // Forward-compat: what kind of redemption this token is for
            $table->enum('intended_use', ['in_person', 'online_checkout'])
                ->default('in_person');

            // Free-form context: branch_id, notes, cart total later, etc.
            $table->json('context')->nullable();

            // Lifecycle
            $table->timestamp('expires_at');
            $table->timestamp('redeemed_at')->nullable();
            $table->foreignId('redeemed_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Hot-path indexes
            $table->index(['token', 'redeemed_at']);          // lookup + one-time check
            $table->index(['coupon_id', 'user_id']);          // per-user limit checks
            $table->index('expires_at');                       // cleanup job
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_redemption_tokens');

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn('scope');
        });
    }
};