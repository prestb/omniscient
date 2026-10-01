<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 12 — LISTING-ATTRIBUTED CONNECTION.
 *
 * An inquiry is attributed to the LISTING that generated it. Business is
 * optional organizational context, because a Listing may legitimately have no
 * Business (a professional/provider Listing).
 *
 * Before: listing_id NULLABLE (never written by the Business-scoped public
 * form), business_id NOT NULL.
 * After:  listing_id REQUIRED and authoritative, business_id NULLABLE context.
 *
 * The FK on listing_id already exists (leads_listing_id_foreign). Only the
 * nullability of the two columns changes; no column is added or dropped.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Never guess an attribution. A lead with no Listing cannot be
        // attributed after this migration.
        $unattributed = DB::table('leads')->whereNull('listing_id')->count();

        if ($unattributed > 0) {
            throw new RuntimeException(
                "Cannot make leads.listing_id authoritative: {$unattributed} lead(s) have a NULL listing_id "
                . 'and no Listing can be inferred for them. Reviews/leads are not auto-assigned; resolve these rows first.'
            );
        }

        Schema::table('leads', function (Blueprint $table) {
            // Business becomes optional context.
            $table->unsignedBigInteger('business_id')->nullable()->change();
        });

        Schema::table('leads', function (Blueprint $table) {
            // The Listing becomes the mandatory attribution subject.
            $table->unsignedBigInteger('listing_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->unsignedBigInteger('listing_id')->nullable()->change();
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->unsignedBigInteger('business_id')->nullable(false)->change();
        });
    }
};
