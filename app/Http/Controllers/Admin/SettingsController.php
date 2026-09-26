<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = [
            'grace_period_days' => Setting::get('grace_period_days', 7),
            'expiring_soon_threshold' => Setting::get('expiring_soon_threshold', 30),
            'check_frequency' => Setting::get('check_frequency', 60),
        ];

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'grace_period_days' => 'required|integer|min:1|max:30',
            'expiring_soon_threshold' => 'required|integer|min:7|max:90',
            'check_frequency' => 'required|integer|min:15|max:1440',
        ]);

        Setting::set('grace_period_days', $validated['grace_period_days'], 'subscription');
        Setting::set('expiring_soon_threshold', $validated['expiring_soon_threshold'], 'subscription');
        Setting::set('check_frequency', $validated['check_frequency'], 'subscription');

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }
}