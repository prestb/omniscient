<?php

namespace App\Traits;

use Illuminate\Http\RedirectResponse;

/**
 * GuardsHiddenItems
 *
 * Provides server-side enforcement so that hidden items (those with
 * `hidden_at` set) cannot be edited or have children added via direct
 * URL access.
 *
 * Hidden items remain:
 *   ✅ Viewable by the owner (in index / show)
 *   ✅ Deletable by the owner (to reduce over-limit count)
 *   ❌ Not editable
 *   ❌ Cannot have children added
 *
 * Usage in a controller:
 *   use GuardsHiddenItems;
 *
 *   public function edit(Business $business) {
 *       if ($redirect = $this->guardNotHidden($business, 'business')) {
 *           return $redirect;
 *       }
 *       // ...
 *   }
 */
trait GuardsHiddenItems
{
    /**
     * If the given model is hidden, return a RedirectResponse.
     * Otherwise return null (so the caller continues normal flow).
     *
     * @param  mixed   $model    Eloquent model with a `hidden_at` column
     * @param  string  $label    Human-readable label used in the error message (e.g., 'business', 'branch')
     * @return RedirectResponse|null
     */
    protected function guardNotHidden($model, string $label = 'item'): ?RedirectResponse
    {
        if (!$model) {
            return null; // Let 404 handle missing models
        }

        if (!$this->isModelHidden($model)) {
            return null;
        }

        $message = "This {$label} is hidden from the public and can only be deleted. "
            . "Delete it or upgrade your plan to restore full access.";

        // Prefer JSON response for AJAX/fetch calls
        if (request()->expectsJson()) {
            abort(403, $message);
        }

        // ✅ Redirect to a safe, known destination instead of back()
        return redirect($this->hiddenRedirectUrl($label))
            ->with('error', $message);

    }

    /**
     * Check if a model has `hidden_at` set.
     */
    protected function isModelHidden($model): bool
    {
        return isset($model->hidden_at) && $model->hidden_at !== null;
    }

    /**
     * Guard the parent business of a nested resource.
     * Useful when the parent being hidden implies children are hidden too.
     */
    protected function guardParentBusiness($business): ?RedirectResponse
    {
        return $this->guardNotHidden($business, 'business');
    }

    /**
     * Determine the safest redirect URL for a locked {$label}.
     */
    protected function hiddenRedirectUrl(string $label): string
    {
        return match ($label) {
            'business' => '/owner/businesses',
            'listing' => '/owner/listings',
            'branch' => '/owner/businesses',   // best we can do without business context
            'service' => '/owner/listings',
            'image' => '/owner/businesses',
            'coupon' => '/owner/coupons',
            default => '/owner/dashboard',
        };
    }
}