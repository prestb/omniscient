<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     *
     * ⚠️ Redirects DIRECTLY to the role-specific dashboard instead of
     *    going through `/dashboard` (which itself redirects). The
     *    extra hop was consuming the success flash before Inertia
     *    could surface it to the frontend.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $user = $request->user();

        // ✅ Verify (only if not already verified)
        if (!$user->hasVerifiedEmail()) {
            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }
        }

        // ✅ Land on the final destination — no intermediate redirect
        $destination = match ($user->role) {
            User::ROLE_OWNER => '/owner/dashboard',
            User::ROLE_ADMIN => '/admin/dashboard',
            User::ROLE_SUPER_ADMIN => '/admin/super-dashboard',
            default => '/user/dashboard',
        };

        return redirect($destination)
            ->with('success', 'Email verified! You can now publish your business.');
    }
}