<?php

namespace App\Http\Requests;

use App\Models\Business;
use App\Support\ListingType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * PHASE 11 / WAVE 1D-2 — Listing creation/update validation.
 *
 * A Listing is created as the discoverable entity. Business association and
 * Location are OPTIONAL (Invariants D and F): a Professional or Store may be
 * created with neither.
 */
class ListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            // The type is explicit — never inferred from Business/Location.
            'type' => ['required', Rule::in(array_map(fn($case) => $case->value, ListingType::cases()))],

            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],

            // Optional organization: only the user's own Businesses.
            'business_id' => [
                'nullable',
                Rule::exists('businesses', 'id')->where(
                    fn($q) => $q->where('owner_id', $userId)->whereNull('deleted_at')
                ),
            ],

            // Optional physical place: only the user's own Locations.
            'location_id' => [
                'nullable',
                Rule::exists('locations', 'id')->where(
                    fn($q) => $q->where('business_id', null)->orWhereIn(
                        'business_id',
                        Business::where('owner_id', $userId)->pluck('id')
                    )
                ),
            ],

            // Creation may publish immediately; otherwise the Listing is a draft.
            'publish' => ['nullable', 'boolean'],

            // PHASE 11 / WAVE 1D-3 — categories are Listing-owned
            // (`listing_categories`). Edited on the Listing, never on a Business.
            'categories' => ['nullable', 'array'],
            'categories.*' => ['exists:categories,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'business_id.exists' => 'That organization does not belong to you.',
            'location_id.exists' => 'That location does not belong to you.',
        ];
    }
}
