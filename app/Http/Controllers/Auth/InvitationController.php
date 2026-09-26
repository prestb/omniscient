<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class InvitationController extends Controller
{
    public function accept($token)
    {
        $invitation = Invitation::where('token', $token)->first();

        if (!$invitation) {
            return redirect()->route('home')
                ->with('error', 'Invalid invitation token.');
        }

        if (!$invitation->isValid()) {
            return redirect()->route('home')
                ->with('error', 'This invitation has expired or has already been used.');
        }

        return Inertia::render('Auth/RegisterInvitation', [
            'invitation' => $invitation,
            'token' => $token,
        ]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|exists:invitations,token',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:50',
            'terms' => 'required|accepted',
        ]);

        $invitation = Invitation::where('token', $validated['token'])->first();

        if (!$invitation->isValid()) {
            return redirect()->route('home')
                ->with('error', 'This invitation has expired or has already been used.');
        }

        // Create user as owner
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? $invitation->phone,
            'role' => 'owner',
            'status' => 'pending',
            'email_verified_at' => now(),
        ]);

        // Mark invitation as used
        $invitation->update([
            'used_at' => now(),
        ]);

        // Log the user in
        Auth::login($user);

        // Redirect to owner dashboard
        return redirect()->route('owner.dashboard')
            ->with('info', 'Your account has been created. Please wait for admin approval.');
    }
}