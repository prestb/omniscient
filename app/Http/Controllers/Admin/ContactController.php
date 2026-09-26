<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContactController extends Controller
{
    /**
     * Display a listing of contact messages.
     */
    public function index(Request $request)
    {
        $query = Contact::query();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search by name, email, or subject
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('subject', 'LIKE', "%{$search}%")
                  ->orWhere('message', 'LIKE', "%{$search}%");
            });
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $contacts = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        // Get counts for statistics
        $stats = [
            'total' => Contact::count(),
            'unread' => Contact::where('status', 'unread')->count(),
            'read' => Contact::where('status', 'read')->count(),
            'replied' => Contact::where('status', 'replied')->count(),
        ];

        return Inertia::render('Admin/Contacts/Index', [
            'contacts' => $contacts,
            'stats' => $stats,
            'filters' => $request->only(['status', 'search', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Display the specified contact message.
     */
    public function show($id)
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
     * Mark a contact message as replied.
     */
    public function markReplied($id)
    {
        $contact = Contact::findOrFail($id);
        $contact->markAsReplied();

        return redirect()->back()->with('success', 'Contact message marked as replied successfully.');
    }

    /**
     * Remove the specified contact message.
     */
        public function destroy($id)
    {
        $contact = Contact::findOrFail($id);
        $contact->delete();

        // ✅ Always redirect to the index. This works whether the delete
        //    was triggered from the list (Index) or the detail page (Show).
        //    If we used back(), deleting from Show would redirect back to a
        //    now-deleted contact (404).
        return redirect()
            ->route('admin.contacts.index')
            ->with('success', 'Contact message deleted successfully.');
    }

    /**
     * Bulk delete contact messages.
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:contacts,id',
        ]);

        Contact::whereIn('id', $request->ids)->delete();

        return redirect()->back()->with('success', 'Selected contact messages deleted successfully.');
    }

    /**
     * Bulk mark as read.
     */
    public function bulkMarkRead(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:contacts,id',
        ]);

        Contact::whereIn('id', $request->ids)->update([
            'status' => 'read',
            'read_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Selected messages marked as read.');
    }
}