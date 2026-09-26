<?php
// app/Services/SentryService.php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SentryService
{
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Resolved API base — depends on the DSN's region.
     * Defaults to US (sentry.io). EU orgs use eu.sentry.io.
     */
    private string $apiBase = 'https://sentry.io/api/0';

    /**
     * Public entry point. Returns null on any failure so the dashboard
     * can render a muted "unavailable" state.
     */
    public function getErrorSummary(): ?array
    {
        $token = config('services.sentry.api_token') ?: env('SENTRY_API_TOKEN');

        if (!$token) {
            return null;
        }

        // Detect region + parse DSN
        $context = $this->resolveContext($token);

        if (!$context) {
            return null;
        }

        $cacheKey = "sentry:summary:{$context['org']}:{$context['project']}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($token, $context) {
            return $this->fetchSummary($token, $context['org'], $context['project']);
        });
    }

    /**
     * Resolve the org slug + project slug for the DSN in use.
     * Also sets $this->apiBase based on the DSN's region.
     * Cached for 24h since these never change.
     */
    private function resolveContext(string $token): ?array
    {
        return Cache::remember('sentry:context', now()->addDay(), function () use ($token) {
            $dsn = config('sentry.dsn') ?: env('SENTRY_LARAVEL_DSN');

            if (!$dsn) {
                Log::warning('Sentry: DSN not configured');
                return null;
            }

            // Extract numeric IDs + region host from the DSN.
            // Format: https://KEY@o{orgId}.ingest[.region].sentry.io/{projectId}
            if (!preg_match('/@o(\d+)\.ingest(\.[a-z]{2})?\.sentry\.io\/(\d+)/', $dsn, $m)) {
                Log::warning('Sentry: could not parse DSN', ['dsn_present' => true]);
                return null;
            }

            $orgNumericId = $m[1];
            $regionSuffix = $m[2] ?? ''; // ".de" for EU, "" for US
            $projectNumericId = $m[3];

            // Set the correct API base for this region
            if ($regionSuffix === '.de') {
                $this->apiBase = 'https://eu.sentry.io/api/0';
            } elseif ($regionSuffix === '.us') {
                $this->apiBase = 'https://us.sentry.io/api/0';
            } else {
                $this->apiBase = 'https://sentry.io/api/0';
            }

            // ============== RESOLVE ORG SLUG ==============
            // Try /organizations/{numericId}/ first (requires org:read)
            $orgSlug = null;

            $orgResponse = $this->apiGet($token, "/organizations/{$orgNumericId}/");
            if (is_array($orgResponse) && !empty($orgResponse['slug'])) {
                $orgSlug = $orgResponse['slug'];
            }

            // Fallback: list all orgs and match by ID
            if (!$orgSlug) {
                $orgsList = $this->apiGet($token, '/organizations/');
                if (is_array($orgsList)) {
                    foreach ($orgsList as $org) {
                        if (isset($org['id']) && (string) $org['id'] === (string) $orgNumericId) {
                            $orgSlug = $org['slug'];
                            break;
                        }
                    }
                }
            }

            if (!$orgSlug) {
                Log::warning('Sentry: org slug not found', ['numeric_id' => $orgNumericId, 'api_base' => $this->apiBase]);
                return null;
            }

            // ============== RESOLVE PROJECT SLUG ==============
            $projectsResponse = $this->apiGet($token, "/organizations/{$orgSlug}/projects/");

            if (!is_array($projectsResponse)) {
                return null;
            }

            $projectSlug = null;
            foreach ($projectsResponse as $project) {
                if (isset($project['id']) && (string) $project['id'] === (string) $projectNumericId) {
                    $projectSlug = $project['slug'];
                    break;
                }
            }

            if (!$projectSlug) {
                Log::warning('Sentry: project slug not found', ['numeric_id' => $projectNumericId]);
                return null;
            }

            return [
                'org' => $orgSlug,
                'project' => $projectSlug,
                'api_base' => $this->apiBase,
            ];
        });
    }

    /**
     * Fetch the error summary for a given org + project.
     */
    private function fetchSummary(string $token, string $org, string $project): ?array
    {
        $stats24h = $this->apiGet(
            $token,
            "/projects/{$org}/{$project}/stats/?stat=received&resolution=1h&statsPeriod=24h"
        );

        $stats7d = $this->apiGet(
            $token,
            "/projects/{$org}/{$project}/stats/?stat=received&resolution=1d&statsPeriod=7d"
        );

        $stats48h = $this->apiGet(
            $token,
            "/projects/{$org}/{$project}/stats/?stat=received&resolution=1h&statsPeriod=48h"
        );

        $issues = $this->apiGet(
            $token,
            "/projects/{$org}/{$project}/issues/?query=is:unresolved&statsPeriod=24h&limit=3&sort=freq"
        );

        if (!is_array($stats24h) || !is_array($stats7d)) {
            return null;
        }

        $errors24h = $this->sumStats($stats24h);
        $errors7d = $this->sumStats($stats7d);
        $errors48h = $this->sumStats($stats48h ?? []);

        $previous24h = max(0, $errors48h - $errors24h);
        $trend = null;
        if ($previous24h > 0) {
            $trend = round((($errors24h - $previous24h) / $previous24h) * 100, 1);
        } elseif ($errors24h > 0) {
            $trend = 100.0;
        } elseif ($errors24h === 0 && $previous24h === 0) {
            $trend = 0.0;
        }

        $unresolved = 0;
        $topIssues = [];

        if (is_array($issues)) {
            foreach ($issues as $issue) {
                $issueCount = isset($issue['count']) ? (int) $issue['count'] : 0;

                if (isset($issue['status']) && $issue['status'] === 'unresolved') {
                    $unresolved++;
                }

                if (count($topIssues) < 3) {
                    $topIssues[] = [
                        'id' => $issue['id'] ?? null,
                        'title' => $issue['title'] ?? 'Unknown',
                        'culprit' => $issue['culprit'] ?? null,
                        'count' => $issueCount,
                        'last_seen' => $issue['lastSeen'] ?? null,
                        'level' => $issue['level'] ?? 'error',
                        'permalink' => $issue['permalink'] ?? null,
                    ];
                }
            }
        }

        return [
            'errors_24h' => $errors24h,
            'errors_7d' => $errors7d,
            'unresolved' => $unresolved,
            'trend' => $trend,
            'top_issues' => $topIssues,
            'last_error_at' => !empty($topIssues) ? ($topIssues[0]['last_seen'] ?? null) : null,
            'sentry_url' => $this->buildOrgUrl($org, $project),
        ];
    }

    /**
     * Build the "View in Sentry" URL using the correct region host.
     */
    private function buildOrgUrl(string $org, string $project): string
    {
        // Map API base to web base
        $webBase = str_replace('/api/0', '', $this->apiBase);
        return "{$webBase}/organizations/{$org}/issues/?project={$project}";
    }

    /**
     * Sentry stats endpoint returns `[[timestamp, count], ...]`.
     */
    private function sumStats(array $stats): int
    {
        $total = 0;
        foreach ($stats as $bucket) {
            if (is_array($bucket) && isset($bucket[1])) {
                $total += (int) $bucket[1];
            }
        }
        return $total;
    }

    /**
     * Single GET helper. Returns null on any failure. Never throws.
     */
    private function apiGet(string $token, string $path): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'Accept' => 'application/json',
            ])
                ->timeout(8)
                ->get($this->apiBase . $path);

            if (!$response->successful()) {
                Log::warning('Sentry API non-2xx', [
                    'base' => $this->apiBase,
                    'path' => $path,
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 200),
                ]);
                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::warning('Sentry API request failed', [
                'base' => $this->apiBase,
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}