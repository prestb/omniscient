<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 11 — SUBSCRIPTION CARDINALITY HARDENING.
 *
 * A Subscription is USER-owned. Application logic in SubscriptionController
 * already guards the normal path with an `exists()` check, but that is
 * check-then-write and two concurrent requests can both pass it.
 *
 * This adds the database boundary using a VIRTUAL generated column plus a
 * unique index. The column evaluates to `user_id` only while the row is in an
 * active lifecycle state, and to NULL otherwise. MySQL/MariaDB permit multiple
 * NULLs in a unique index, so any number of inactive subscriptions may coexist
 * while only one active-lifecycle subscription per User can exist.
 *
 * Verified against the project's actual server (MariaDB 10.4.32) rather than
 * assumed: CREATE and ALTER TABLE both accept a VIRTUAL generated column with a
 * unique index, and a duplicate active row is rejected with error 1062.
 *
 * `business_id` is untouched: it remains optional organizational context.
 */
return new class extends Migration
{
    /** Lifecycle states that occupy a User's single active slot. */
    private string $activeStates = "'active','expiring_soon','grace_period'";

    public function up(): void
    {
        // ── Validate existing data BEFORE adding the invariant ──────────────
        $conflicts = DB::select(
            "select user_id, count(*) c from subscriptions
             where status in ({$this->activeStates})
             group by user_id having count(*) > 1"
        );

        if (!empty($conflicts)) {
            $detail = collect($conflicts)
                ->map(fn($r) => "user {$r->user_id} ({$r->c} active)")
                ->implode(', ');

            throw new RuntimeException(
                "Cannot enforce one active subscription per user: existing conflicts -> {$detail}. "
                . 'Resolve these rows manually; this migration will not merge or delete subscriptions.'
            );
        }

        $unownedActive = DB::table('subscriptions')
            ->whereIn('status', ['active', 'expiring_soon', 'grace_period'])
            ->whereNull('user_id')
            ->count();

        if ($unownedActive > 0) {
            throw new RuntimeException(
                "Cannot enforce one active subscription per user: {$unownedActive} active subscription(s) "
                . 'have a NULL user_id and therefore no owner.'
            );
        }

        $unownedPending = DB::table('subscriptions')
            ->where('status', 'pending')
            ->whereNull('user_id')
            ->count();

        if ($unownedPending > 0) {
            throw new RuntimeException(
                "Cannot enforce one pending subscription per user: {$unownedPending} pending subscription(s) "
                . 'have a NULL user_id.'
            );
        }

        // ── One ACTIVE lifecycle subscription per User ──────────────────────
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('active_user_id')
                ->nullable()
                ->virtualAs("case when `status` in ({$this->activeStates}) then `user_id` else null end")
                ->after('business_id');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unique('active_user_id', 'subscriptions_active_user_unique');
        });

        // ── One PENDING subscription per User ───────────────────────────────
        // The initiation path already uses updateOrCreate(['user_id', PENDING]).
        // This closes the same check-then-write race for pending rows.
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('pending_user_id')
                ->nullable()
                ->virtualAs("case when `status` = 'pending' then `user_id` else null end")
                ->after('active_user_id');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unique('pending_user_id', 'subscriptions_pending_user_unique');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropUnique('subscriptions_pending_user_unique');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropUnique('subscriptions_active_user_unique');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['pending_user_id', 'active_user_id']);
        });
    }
};
