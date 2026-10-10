<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 24-R1 — REMOVE THE BUSINESS-SCOPED SUBSCRIPTION ARCHITECTURE.
 *
 * A subscription belongs to the USER/ACCOUNT and to nothing else:
 *
 *     User -> Subscription -> Plan -> Entitlements
 *
 * Two Business-scoped artefacts from the retired model were still executable:
 *
 *   1. `subscriptions.business_id` — mass-assignable, backed by a FOREIGN KEY to
 *      `businesses.id` and by TWO indexes, and used by an admin endpoint that
 *      listed subscriptions BY BUSINESS.
 *
 *   2. `businesses.has_active_subscription` — a stored column that
 *      `Business::getHasActiveSubscriptionAttribute()` SHADOWS, so the two Fapshi
 *      writes that targeted it stored a value no reader could ever see. The
 *      accessor derives the answer from the owner's subscription, which is the
 *      correct account-scoped source and is deliberately KEPT.
 *
 * WHY THE SCHEMA IS INSPECTED BEFORE THE CLOSURE. `subscriptions.business_id` is
 * NOT a standalone column: it carries `subscriptions_business_id_foreign`,
 * `subscriptions_business_id_index` and `subscriptions_business_id_status_index`.
 * Dropping the column without first dropping those fails on MySQL/MariaDB, and
 * the dependent objects are discovered with `Schema::getForeignKeys()` /
 * `Schema::getIndexes()` OUTSIDE the table-definition closure — Laravel's schema
 * builder does not support safe conditional inspection from inside it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('subscriptions', 'business_id')) {
            // Discover every dependent object that touches the column, so the
            // drop does not depend on hardcoded index or constraint names.
            $foreignKeys = collect(Schema::getForeignKeys('subscriptions'))
                ->filter(fn (array $fk) => in_array('business_id', $fk['columns'] ?? [], true))
                ->pluck('name')
                ->all();

            $indexes = collect(Schema::getIndexes('subscriptions'))
                ->reject(fn (array $index) => ($index['primary'] ?? false) === true)
                ->filter(fn (array $index) => in_array('business_id', $index['columns'] ?? [], true))
                ->pluck('name')
                ->all();

            Schema::table('subscriptions', function (Blueprint $table) use ($foreignKeys, $indexes) {
                foreach ($foreignKeys as $name) {
                    $table->dropForeign($name);
                }

                foreach ($indexes as $name) {
                    $table->dropIndex($name);
                }
            });

            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropColumn('business_id');
            });
        }

        if (Schema::hasColumn('businesses', 'has_active_subscription')) {
            Schema::table('businesses', function (Blueprint $table) {
                $table->dropColumn('has_active_subscription');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('subscriptions', 'business_id')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->foreignId('business_id')->nullable()->after('id')
                    ->constrained('businesses')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('businesses', 'has_active_subscription')) {
            Schema::table('businesses', function (Blueprint $table) {
                $table->boolean('has_active_subscription')->default(false);
            });
        }
    }
};
