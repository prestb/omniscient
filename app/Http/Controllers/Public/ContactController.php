<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class ContactController extends Controller
{
    /**
     * Show the contact page
     */
    public function index()
    {
        return Inertia::render('Public/Contact');
    }

    /**
     * Store a new contact message
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|min:10',
        ]);

        // Save to database
        $contact = Contact::create($validated);

        // Send email notification
        try {
            Mail::send('emails.contact-notification', [
                'contact' => $contact,
            ], function ($message) use ($contact) {
                $message->to(config('mail.contact_email', 'admin@omniscient.cm'))
                        ->subject('New Contact Message: ' . $contact->subject)
                        ->replyTo($contact->email, $contact->name);
            });

            // Send auto-reply to user
            Mail::send('emails.contact-autoreply', [
                'contact' => $contact,
            ], function ($message) use ($contact) {
                $message->to($contact->email, $contact->name)
                        ->subject('Thank you for contacting Omniscient');
            });

        } catch (\Exception $e) {
            // Log error but continue
            \Log::error('Contact email failed: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Your message has been sent successfully! We\'ll get back to you soon.');
    }

    /**
     * Admin: View all contact messages
     */
    public function adminIndex()
    {
        $contacts = Contact::orderBy('created_at', 'desc')->paginate(20);
        
        return Inertia::render('Admin/Contacts/Index', [
            'contacts' => $contacts,
        ]);
    }

    /**
     * Admin: View single contact message
     */
    public function adminShow($id)
    {
        $contact = Contact::findOrFail($id);
        
        // Mark as read if unread
        if ($contact->status === 'unread') {
            $contact->markAsRead();
        }
        
        return Inertia::render('Admin/Contacts/Show', [
            'contact' => $contact,
        ]);
    }

    /**
     * Admin: Delete contact message
     */
    public function adminDestroy($id)
    {
        $contact = Contact::findOrFail($id);
        $contact->delete();
        
        return redirect()->back()->with('success', 'Contact message deleted successfully.');
    }

    /**
     * Admin: Mark as replied
     */
    public function adminMarkReplied($id)
    {
        $contact = Contact::findOrFail($id);
        $contact->markAsReplied();
        
        return redirect()->back()->with('success', 'Contact message marked as replied.');
    }
}