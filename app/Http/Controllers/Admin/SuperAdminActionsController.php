<?php
// app/Http/Controllers/Admin/SuperAdminActionsController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SuperAdminActionsController extends Controller
{
    /**
     * Turn maintenance mode ON or OFF.
     * Payload: { action: 'on' | 'off' }
     */
    public function toggleMaintenance(Request $request)
    {
        $this->guardSuperAdmin();

        $validated = $request->validate([
            'action' => 'required|in:on,off',
        ]);

        $action = $validated['action'];
        $downFile = storage_path('framework/down');

        if ($action === 'on') {
            if (app()->isDownForMaintenance()) {
                return back()->with('info', 'Maintenance mode is already active.');
            }

            $adminIp = $request->ip();
            $secret = Str::random(48);

            // Build the allowlist: always include both localhost variants
            // plus the current admin's IP.
            $allowList = array_values(array_unique(array_filter([
                '127.0.0.1',
                '::1',
                $adminIp,
            ])));

            $payload = [
                'time' => now()->getTimestamp(),
                'message' => 'Platform is under maintenance. We will be back shortly.',
                'retry' => 60,
                'allow' => $allowList,
                'secret' => $secret,
                'by' => Auth::user()->name,
                'render' => 'errors::503',
            ];

            try {
                File::ensureDirectoryExists(dirname($downFile));
                File::put($downFile, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            } catch (\Throwable $e) {
                return back()->with('error', 'Failed to enable maintenance mode: ' . $e->getMessage());
            }

            $bypassUrl = url('/' . $secret);

            // Long-duration toast so the admin has time to copy the bypass URL
            return back()
                ->with('success', "Maintenance mode is ON. Bypass URL: {$bypassUrl} — visit it once to keep your browser signed in.")
                ->with('maintenance_bypass_url', $bypassUrl);
        }

        // OFF — delete the down file
        if (!app()->isDownForMaintenance()) {
            return back()->with('info', 'Maintenance mode is already off.');
        }

        try {
            if (File::exists($downFile)) {
                File::delete($downFile);
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to disable maintenance mode: ' . $e->getMessage());
        }

        return back()->with('success', 'Maintenance mode is OFF. The platform is live again.');
    }

    /**
     * Clear application cache.
     */
    public function clearCache()
    {
        $this->guardSuperAdmin();

        try {
            Artisan::call('cache:clear');
            $output = trim(Artisan::output());

            return back()->with('success', 'Application cache cleared.' . ($output ? " ({$output})" : ''));
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to clear cache: ' . $e->getMessage());
        }
    }

    /**
     * Clear compiled + cached files (route, config, view, events, etc.).
     */
    public function optimizeClear()
    {
        $this->guardSuperAdmin();

        try {
            Artisan::call('optimize:clear');
            $output = trim(Artisan::output());

            return back()->with('success', 'Compiled & cached files cleared.' . ($output ? " ({$output})" : ''));
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to optimize clear: ' . $e->getMessage());
        }
    }

    /**
     * Gracefully restart all queue workers.
     */
    public function restartQueue()
    {
        $this->guardSuperAdmin();

        try {
            Artisan::call('queue:restart');

            return back()->with('success', 'Queue workers are restarting. Active jobs will finish, then workers reload.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to restart queue: ' . $e->getMessage());
        }
    }

    /**
     * Defensive check — always require super_admin.
     * Routes are already gated, but this prevents misuse if the route
     * middleware is ever misconfigured.
     */
    private function guardSuperAdmin(): void
    {
        $user = Auth::user();

        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Only super admins can perform this action.');
        }
    }
}