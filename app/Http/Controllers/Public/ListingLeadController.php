<?php

namespace App\Http\Controllers\Public;

use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 12 — LISTING-ATTRIBUTED CONNECTION.
 *
 * An inquiry is attributed to the LISTING that generated it. The visitor submits
 * against the exact Listing they are viewing (route model binding on the slug),
 * so there is no representative-Listing selection anywhere in this path.
 *
 * `business_id` is OPTIONAL CONTEXT and is always DERIVED SERVER-SIDE from the
 * Listing — a visitor can never submit it. A Listing with no Business produces a
 * lead with `business_id = NULL`, which is a first-class case.
 */
class ListingLeadController extends Controller
{
    public function storeListing(Request $request, Listing $listing)
    {
        // Only a published, visible Listing may receive public inquiries.
        // `isVisibleToPublic()` is the same rule the public profile page uses.
        if (!$listing->isVisibleToPublic()) {
            return response()->json([
                'success' => false,
                'message' => 'This listing is not currently accepting inquiries.',
            ], 404);
        }

        // Entitlement: evaluated in the context of the entity RECEIVING the
        // inquiry. A Business-owned Listing uses the organization's lead-capture
        // feature, exactly as before. A Business-less Listing has no plan of its
        // own to consume — before Phase 12 it could not receive an inquiry at
        // all — so it is permitted rather than silently dropped.
        if ($listing->business && !$listing->business->hasLeadCaptureFeature()) {
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

        if (empty($validated['email']) && empty($validated['phone']) && empty($validated['whatsapp'])) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide at least one contact method (email, phone, or WhatsApp).',
            ], 422);
        }

        try {
            $lead = Lead::create([
                // AUTHORITATIVE: the exact Listing the visitor was viewing.
                'listing_id' => $listing->id,
                // OPTIONAL CONTEXT: derived from the Listing, never submitted.
                'business_id' => $listing->business_id,
                'source' => 'listing_contact_form',
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

            // Notify the owner. The Listing identifies WHAT generated the inquiry;
            // the owner/account remains the party authorized to manage it.
            $owner = $listing->owner;
            if ($owner) {
                NotificationHelper::send(
                    $owner,
                    'New inquiry received',
                    "{$lead->name} sent an inquiry about {$listing->name}.",
                    "/owner/leads/{$lead->id}",
                    [
                        'lead_id' => $lead->id,
                        'listing_id' => $listing->id,
                        'listing_name' => $listing->name,
                        'business_id' => $listing->business_id,
                        'sender_name' => $lead->name,
                    ],
                    'new_lead'
                );
            }

            Log::info('Listing inquiry captured', [
                'lead_id' => $lead->id,
                'listing_id' => $listing->id,
                'business_id' => $listing->business_id,
                'sender' => $lead->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Thank you! Your inquiry has been sent.',
                'lead_id' => $lead->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create listing inquiry', [
                'error' => $e->getMessage(),
                'listing_id' => $listing->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }
}
