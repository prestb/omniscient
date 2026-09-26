<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessAnalytics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AnalyticsController extends Controller
{
    public function trackView($businessId)
    {
        try {
            Log::info('Tracking view for business: ' . $businessId);
            $business = Business::findOrFail($businessId);
            BusinessAnalytics::trackView($business->id);
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error tracking view: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function trackClick(Request $request, $businessId, $type)
    {
        try {
            Log::info('Tracking click for business: ' . $businessId . ' type: ' . $type);
            
            $business = Business::findOrFail($businessId);
            
            // Validate click type
            $validTypes = ['phone', 'whatsapp', 'website', 'direction', 'social'];
            if (!in_array($type, $validTypes)) {
                Log::warning('Invalid click type: ' . $type);
                return response()->json(['error' => 'Invalid click type'], 400);
            }
            
            $result = BusinessAnalytics::trackClick($business->id, $type);
            
            if ($result) {
                Log::info('Click tracked successfully for: ' . $businessId . ' type: ' . $type);
                return response()->json(['success' => true]);
            } else {
                return response()->json(['error' => 'Failed to track click'], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error tracking click: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}