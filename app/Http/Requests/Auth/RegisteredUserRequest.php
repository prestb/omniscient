<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        // Enhanced validation with custom messages
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
            'email' => [
                'required', 
                'string', 
                'lowercase', 
                'email', 
                'max:255', 
                'unique:users,email',
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'
            ],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            'phone' => [
                'nullable', 
                'string', 
                'max:50', 
                'regex:/^[0-9+\-\s()]+$/',
                'min:8',
            ],
            'terms' => ['required', 'accepted'],
        ], [
            'name.regex' => 'Name should only contain letters and spaces.',
            'email.regex' => 'Please enter a valid email address.',
            'password.uncompromised' => 'This password appears in a data breach. Please choose a different password.',
            'phone.regex' => 'Please enter a valid phone number.',
            'phone.min' => 'Phone number must be at least 8 characters.',
            'terms.accepted' => 'You must agree to the Terms of Service to continue.',
        ]);

        // Sanitize inputs
        $phone = $request->phone ? preg_replace('/[^0-9+]/', '', $request->phone) : null;

        // Create user with additional fields
        $user = User::create([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'phone' => $phone,
            'password' => Hash::make($validated['password']),
            'role' => 'owner',
            'status' => 'pending',
            'email_verified_at' => null, // Requires email verification
            'failed_login_attempts' => 0,
        ]);

        // Log the registration
        \Log::info('New user registered', [
            'email' => $user->email, 
            'id' => $user->id,
            'ip' => $request->ip(),
        ]);

        // Send email verification notification
        $user->sendEmailVerificationNotification();

        // Fire registration event
        event(new Registered($user));

        // Log the user in
        Auth::login($user);

        // Redirect with pending verification message
        return redirect()->route('verification.notice')
            ->with('success', 'Registration successful! Please verify your email address.');
    }
}