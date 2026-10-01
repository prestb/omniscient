<?php

namespace App\Services;

use App\Models\Business;
use App\Support\DataOwnership;
use App\Support\ListingIdentity;
use App\Support\ListingOwnership;

/**
 * MigrationReadinessService
 *
 * Phase 4/5/6 — READ-ONLY readiness verdict for a future Location → Listing migration.
 *
 * Consumes the read-only ListingMigrationAnalyzer output plus live counts and
 * produces:
 *   - the ownership tallies (listing / branch / ambiguous / derived)
 *   - a readiness verdict (READY | NOT_READY)
 *   - explicit, human-readable blocking reasons
 *
 * Phase 5 refinement: an entity is only BLOCKING when it has NO canonical
 * policy (see {@see ListingOwnership}). Entities that were "ambiguous" in
 * Phase 4 but now have a decided policy (reviews, services, categories,
 * images, contacts, leads, coupons, favorites, analytics) no longer block —
 * they are *resolved ambiguities*, reported separately.
 *
 * Phase 6 refinement: the report also exposes the selected coexistence
 * architecture and the readiness STAGE vocabulary (POLICY_READY /
 * STRUCTURE_READY / MIGRATION_READY / MIGRATION_VERIFIED). This is semantic
 * clarity only — no state machine or migration machinery is built.
 *
 * ── HARD GUARANTEE: performs NO writes. ──
 * A test asserts the database is unchanged after a readiness run.
 */
class MigrationReadinessService
{
    public const READY = 'READY';
    public const NOT_READY = 'NOT_READY';

    // Phase 6 readiness stages (vocabulary, not a persisted state machine).
    public const STAGE_POLICY_READY = 'POLICY_READY';
    public const STAGE_STRUCTURE_READY = 'STRUCTURE_READY';
    public const STAGE_MIGRATION_READY = 'MIGRATION_READY';
    public const STAGE_MIGRATION_VERIFIED = 'MIGRATION_VERIFIED';

    public function __construct(
        private readonly ListingMigrationAnalyzer $analyzer
    ) {
    }

    /**
     * Produce the readiness report. Read-only.
     *
     * @return array{
     *     business: array,
     *     tally: array<string,int>,
     *     readiness: string,
     *     blocking_reasons: array<int,string>,
     *     ownership: array<string, mixed>
     * }
     */
    public function report(Business $business): array
    {
        $analysis = $this->analyzer->analyze($business);
        $business = $business->fresh(['locations']) ?? $business;

        $branchCount = $business->locations->count();

        // ── Tally from the analyzer classification ────────────────────
        $tally = [
            DataOwnership::LISTING_OWNED => $this->countBucket($analysis, ListingMigrationAnalyzer::CLASS_BUSINESS_LEVEL),
            DataOwnership::LOCATION_OWNED => $this->countBucket($analysis, ListingMigrationAnalyzer::CLASS_LOCATION_LEVEL),
            DataOwnership::SHARED_AMBIGUOUS => $this->countBucket($analysis, ListingMigrationAnalyzer::CLASS_SHARED_AMBIGUOUS),
            DataOwnership::DERIVED_SYSTEM => $this->countBucket($analysis, ListingMigrationAnalyzer::CLASS_DERIVED_SYSTEM),
        ];

        $blocking = $this->blockingReasons($analysis, $branchCount);
        $resolved = $this->resolvedAmbiguities($analysis, $branchCount);
        $readiness = empty($blocking) ? self::READY : self::NOT_READY;

        return [
            'business' => $analysis['business'],
            'tally' => $tally,
            'readiness' => $readiness,
            'stage' => $this->stage($readiness),
            'blocking_reasons' => $blocking,
            'resolved_ambiguities' => $resolved,
            'ownership' => [
                'matrix' => DataOwnership::matrix(),
                'ambiguous_entities' => DataOwnership::ambiguousEntities(),
                'resolved_by_policy' => DataOwnership::resolvedByPolicy(),
                'unresolved_ambiguous_entities' => DataOwnership::unresolvedAmbiguousEntities(),
                'branch_owned_entities' => DataOwnership::branchOwnedEntities(),
                'account_owned_entities' => DataOwnership::accountOwnedEntities(),
                'parent_identity_model' => ListingOwnership::parentIdentity(),
            ],
            'identity_model' => [
                'selected_architecture' => ListingIdentity::SELECTED_OPTION,
                'canonical_parent' => ListingIdentity::canonicalParent(),
                'independent_listing' => ListingIdentity::independentListing(),
                'readiness_vocabulary' => ListingIdentity::readinessVocabulary(),
            ],
        ];
    }

    /**
     * Phase 6 — map the READY/NOT_READY verdict onto the readiness STAGE
     * vocabulary. This is a pure derivation (no persisted state machine).
     *
     *   NOT_READY → POLICY or STRUCTURE work outstanding.
     *   READY     → MIGRATION_READY (a specific business may be migrated).
     *
     * MIGRATION_VERIFIED is only ever reached AFTER a migration has run and
     * been verified — which cannot happen in a read-only phase, so this
     * service never returns it.
     */
    public function stage(string $readiness): string
    {
        return $readiness === self::READY
            ? self::STAGE_MIGRATION_READY
            : self::STAGE_STRUCTURE_READY;
    }

    /**
     * Ambiguities that a Phase 5 policy has RESOLVED (informational — these do
     * NOT block a future migration because their destination is now defined).
     *
     * @return array<int, string>
     */
    private function resolvedAmbiguities(array $analysis, int $branchCount): array
    {
        if ($branchCount < 2) {
            return [];
        }

        $resolved = [];
        $ambiguous = collect($analysis['classification'][ListingMigrationAnalyzer::CLASS_SHARED_AMBIGUOUS] ?? []);

        foreach ($ambiguous as $item) {
            $count = (int) ($item['count'] ?? 0);
            if ($count <= 0) {
                continue;
            }
            if (DataOwnership::isResolved($item['entity'])) {
                $resolved[] = "{$count} {$item['entity']} record(s) — destination defined by Phase 5 policy";
            }
        }

        return $resolved;
    }

    /**
     * Sum the count column of a classification bucket. Items without a count
     * contribute 1 (they are singular entities).
     */
    private function countBucket(array $analysis, string $bucket): int
    {
        $total = 0;
        foreach ($analysis['classification'][$bucket] ?? [] as $item) {
            $total += array_key_exists('count', $item) ? (int) $item['count'] : 1;
        }
        return $total;
    }

    /**
     * Derive blocking reasons from the analysis. A business with more than one
     * branch and any shared/ambiguous business-level records is NOT READY.
     *
     * @return array<int, string>
     */
    private function blockingReasons(array $analysis, int $branchCount): array
    {
        $reasons = [];

        if ($branchCount < 2) {
            // A single-branch business has nothing meaningful to fan out;
            // it is trivially "ready" only in the sense of having no split.
            return $reasons;
        }

        $ambiguous = collect($analysis['classification'][ListingMigrationAnalyzer::CLASS_SHARED_AMBIGUOUS] ?? []);

        foreach ($ambiguous as $item) {
            $count = (int) ($item['count'] ?? 0);
            if ($count <= 0) {
                continue;
            }

            // Phase 5: an entity with a canonical policy no longer blocks —
            // its destination is defined. ONLY entities lacking a policy block.
            if (DataOwnership::isResolved($item['entity'])) {
                continue;
            }

            $reasons[] = "{$count} {$item['entity']} record(s) have NO defined destination policy — resolve before migrating";
        }

        return $reasons;
    }
}
