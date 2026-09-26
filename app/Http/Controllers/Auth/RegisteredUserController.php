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
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
{
    $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
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
        'phone' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+\-\s()]+$/'],
        'terms' => ['required', 'accepted'],
        'account_type' => ['required', 'string', 'in:user,owner'], // ✅
    ], [
        'password.uncompromised' => 'This password appears in a data breach. Please choose a different password.',
        'phone.regex' => 'Please enter a valid phone number.',
        'terms.accepted' => 'You must agree to the Terms of Service.',
    ]);

    $phone = $request->phone ? preg_replace('/[^0-9+]/', '', $request->phone) : null;

    // ✅ Determine role based on account type
    $isOwner = $request->input('account_type') === 'owner';

    $user = User::create([
        'name' => $request->name,
        'email' => strtolower($request->email),
        'phone' => $phone,
        'password' => Hash::make($request->password),
        'role' => $isOwner ? User::ROLE_OWNER : User::ROLE_USER,
        'status' => User::STATUS_ACTIVE,
    ]);

    \Log::info('New user registered', [
        'email' => $user->email,
        'id' => $user->id,
        'role' => $user->role,
        'account_type' => $request->input('account_type'),
    ]);

    event(new Registered($user));

    Auth::login($user);

    // ✅ Redirect based on account type
    if ($isOwner) {
        return redirect()->route('owner.businesses.create')
            ->with('info', 'Welcome! Let\'s set up your business profile.');
    }

    return redirect()->route('user.dashboard');
}
}