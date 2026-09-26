<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\InvitationMail;
use App\Models\Invitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class InvitationController extends Controller
{
    public function index()
    {
        $invitations = Invitation::with('invitedBy')
            ->latest()
            ->paginate(20);

        return Inertia::render('Admin/Invitations/Index', [
            'invitations' => $invitations,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Invitations/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:invitations,email,NULL,id,used_at,NULL',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'business_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'expires_in_days' => 'required|integer|min:1|max:30',
            'send_email' => 'boolean',
        ]);

        $invitation = Invitation::create([
            'email' => $validated['email'],
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'business_name' => $validated['business_name'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'invited_by' => auth()->id(),
            'expires_at' => now()->addDays($validated['expires_in_days']),
        ]);

        // ✅ Only send the email if the admin requested it. The invitation
        //    itself is always created so it can be resent later from the
        //    index page.
        if ($request->boolean('send_email', true)) {
            Mail::to($invitation->email)->send(new InvitationMail($invitation));

            return redirect()->route('admin.invitations.index')
                ->with('success', 'Invitation created and sent successfully!');
        }

        return redirect()->route('admin.invitations.index')
            ->with('success', 'Invitation created. Email not sent.');
    }

    public function resend(Invitation $invitation)
    {
        if ($invitation->isUsed()) {
            return redirect()->back()->with('error', 'This invitation has already been used.');
        }

        // Reset expiration
        $invitation->update([
            'expires_at' => now()->addDays(7),
        ]);

        Mail::to($invitation->email)->send(new InvitationMail($invitation));

        return redirect()->back()->with('success', 'Invitation resent successfully!');
    }

    public function destroy(Invitation $invitation)
    {
        if ($invitation->isUsed()) {
            return redirect()->back()->with('error', 'Cannot delete used invitation.');
        }

        $invitation->delete();

        return redirect()->route('admin.invitations.index')
            ->with('success', 'Invitation deleted successfully.');
    }
}