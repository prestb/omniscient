<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        Setting::set('grace_period_days', 7, 'subscription');
        Setting::set('expiring_soon_threshold', 30, 'subscription');
        Setting::set('check_frequency', 60, 'subscription');
    }
}