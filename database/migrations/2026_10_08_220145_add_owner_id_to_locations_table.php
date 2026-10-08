<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 22A — CANONICAL LOCATION OWNERSHIP.
 *
 * A Location is an account-owned resource. `owner_id` is the authoritative
 * authorization relationship; `business_id` remains only as optional
 * organization context and must never be the ownership check.
 *
 * This makes a Business-less Professional Location representable:
 *
 *     owner_id = <Professional user>
 *     business_id = NULL
 *
 * MIGRATION STRATEGY
 *   A NOT NULL column cannot be added to populated rows without a value. So the
 *   column is added NULLABLE, existing rows are attributed FROM THEIR BUSINESS
 *   OWNER where that is unambiguous, and only then is NOT NULL enforced.
 *
 *   No ownership is invented: a row whose Business has no owner, or which has
 *   neither a Business nor any other deterministic owner, is left NULL and
 *   reported rather than assigned an arbitrary user.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('locations', 'owner_id')) {
            return;
        }

        Schema::table('locations', function (Blueprint $table) {
            $table->foreignId('owner_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->cascadeOnDelete();
        });

        // Attribute existing rows from their Business owner — deterministic only.
        $unattributable = DB::table('locations')
            ->leftJoin('businesses', 'businesses.id', '=', 'locations.business_id')
            ->whereNull('locations.owner_id')
            ->where(function ($q) {
                $q->whereNull('locations.business_id')
                  ->orWhereNull('businesses.owner_id');
            })
            ->count();

        if ($unattributable > 0) {
            // Do NOT invent an owner. Report and leave them NULL so NOT NULL is
            // not enforced over rows we cannot attribute honestly.
            logger()->warning(
                "PHASE 22A: {$unattributable} location(s) could not be attributed "
                . 'to an owner (no Business, or Business without an owner). They are '
                . 'left NULL; owner_id NOT NULL was not enforced.'
            );

            return;
        }

        DB::statement(
            'UPDATE locations l
             JOIN businesses b ON b.id = l.business_id
             SET l.owner_id = b.owner_id
             WHERE l.owner_id IS NULL AND b.owner_id IS NOT NULL'
        );

        Schema::table('locations', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable(false)->change();
            $table->index('owner_id', 'locations_owner_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropIndex('locations_owner_id_index');
            $table->dropForeign(['owner_id']);
            $table->dropColumn('owner_id');
        });
    }
};