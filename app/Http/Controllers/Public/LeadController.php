<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Helpers\NotificationHelper;
use App\Models\Business;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LeadController extends Controller
{
    public function store(Request $request, Business $business)
    {
        // ✅ Check if business has lead capture feature
        if (!$business->hasLeadCaptureFeature()) {
            return response()->json([
                'success' => false,
                'message' => 'This business does not accept contact forms.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'whatsapp' => 'nullable|string|max:50',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        // Require at least one contact method
        if (empty($validated['email']) && empty($validated['phone']) && empty($validated['whatsapp'])) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide at least one contact method (email, phone, or WhatsApp).',
            ], 422);
        }

        try {
            $lead = Lead::create([
                'business_id' => $business->id,
                'branch_id' => $business->primaryBranch?->id,
                'source' => 'contact_form',
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'whatsapp' => $validated['whatsapp'] ?? null,
                'subject' => $validated['subject'] ?? null,
                'message' => $validated['message'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => Lead::STATUS_NEW,
            ]);

            // ✅ Notify owner (push + database)
            $owner = $business->owner;
            if ($owner) {
                NotificationHelper::send(
                    $owner,
                    'New Lead Received 📬',
                    "{$lead->name} sent you a message about {$business->name}.",
                    "/owner/leads/{$lead->id}",
                    [
                        'lead_id' => $lead->id,
                        'business_id' => $business->id,
                        'sender_name' => $lead->name,
                    ],
                    'new_lead'
                );
            }

            Log::info('Lead captured', [
                'lead_id' => $lead->id,
                'business_id' => $business->id,
                'sender' => $lead->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Thank you! Your message has been sent. The business will get back to you soon.',
                'lead_id' => $lead->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create lead', [
                'error' => $e->getMessage(),
                'business_id' => $business->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }
}