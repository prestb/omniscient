<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        // Resolve the impersonator (if any)
        $impersonatorId = $request->session()->get('impersonator_id');
        $impersonator = $impersonatorId ? User::find($impersonatorId) : null;

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user(),
                'plan' => $request->user()?->getCurrentPlan(),
                'usage' => $request->user() ? [
                    'listings' => $request->user()->getCurrentUsage('listings'),
                    'locations' => $request->user()->getCurrentUsage('locations'),
                    'services' => $request->user()->getCurrentUsage('services'),
                    'images' => $request->user()->getCurrentUsage('images'),
                    'coupons' => $request->user()->getCurrentUsage('coupons'),
                ] : [],
                'impersonator' => $impersonator ? [
                    'id' => $impersonator->id,
                    'name' => $impersonator->name,
                    'email' => $impersonator->email,
                ] : null,
            ],

            // ✅ Share flash messages globally — pull() consumes them so they
            // only fire once per flash, not on every subsequent request.
            'flash' => [
                'success' => fn() => $request->session()->pull('success'),
                'error' => fn() => $request->session()->pull('error'),
                'warning' => fn() => $request->session()->pull('warning'),
                'info' => fn() => $request->session()->pull('info'),
                'message' => fn() => $request->session()->pull('message'),
                'maintenance_bypass_url' => fn() => $request->session()->pull('maintenance_bypass_url'),
            ],

            'ziggy' => function () {
                return (new Ziggy)->toArray();
            },
        ]);
    }
}