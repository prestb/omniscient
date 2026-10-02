// resources/js/urls.js
//
// PHASE 17 — CANONICAL PUBLIC URL HELPERS.
//
// A single defect has now appeared four times: a LISTING slug placed on the
// BUSINESS route.
//
//   ListingCard      (Wave 1D-6)
//   Collection       (Phase 15E)
//   ExploreCard      (Phase 16C)
//   DirectoryMap / SearchBar / SearchAutocomplete (Phase 16F)
//
// It survived every build because `/business/${listing.slug}` is syntactically
// valid — it only fails at runtime, when the route cannot resolve. Each previous
// fix corrected only the site where the bug was noticed, which is why it kept
// recurring somewhere else.
//
// These helpers make the two entities explicit and impossible to confuse:
//
//     listingUrl(listing)   -> /listing/{slug}
//     businessUrl(business) -> /business/{slug}
//
// Neither is a generic "make a URL" function. Passing a Listing to businessUrl
// is a mistake the call site has to spell out.

/** The canonical Listing URL. Accepts a Listing object or a bare slug. */
export function listingUrl(listing) {
    const slug = typeof listing === 'string' ? listing : listing?.slug;

    return slug ? `/listing/${encodeURIComponent(slug)}` : null;
}

/** The organization URL. Accepts a Business object or a bare slug. */
export function businessUrl(business) {
    const slug = typeof business === 'string' ? business : business?.slug;

    return slug ? `/business/${encodeURIComponent(slug)}` : null;
}

/**
 * Contact links for LISTING-owned contacts.
 *
 * Values come from `listing_contacts` (Listing-owned). Nothing is fabricated:
 * an absent or blank value yields null and the caller renders no control.
 */
export function contactHref(contact) {
    const type = contact?.type;
    const value = String(contact?.value ?? '').trim();

    if (!value) return null;

    switch (type) {
        case 'phone':
            return `tel:${value}`;
        case 'whatsapp':
            // Digits only: wa.me takes an international number without symbols.
            return `https://wa.me/${value.replace(/\D/g, '')}`;
        case 'website':
            return /^https?:\/\//i.test(value) ? value : `https://${value}`;
        case 'facebook':
        case 'instagram':
        case 'tiktok':
        case 'twitter':
        case 'youtube':
        case 'linkedin':
            return /^https?:\/\//i.test(value) ? value : `https://${value}`;
        default:
            return null;
    }
}

/** Human label for a Listing contact type. */
export function contactLabel(type) {
    const labels = {
        phone: 'Phone',
        whatsapp: 'WhatsApp',
        website: 'Website',
        facebook: 'Facebook',
        instagram: 'Instagram',
        tiktok: 'TikTok',
        twitter: 'X',
        youtube: 'YouTube',
        linkedin: 'LinkedIn',
        other: 'Contact',
    };

    return labels[type] ?? 'Contact';
}

/**
 * PHASE 18B — map a Listing contact type onto the analytics click vocabulary
 * that already exists server-side:
 *
 *     phone | whatsapp | website | direction | social
 *
 * No new click type is invented. Contact types with no corresponding public
 * action (e.g. `other`) return null and are simply not measured.
 */
export function clickTypeFor(contactType) {
    switch (contactType) {
        case 'phone':
            return 'phone';
        case 'whatsapp':
            return 'whatsapp';
        case 'website':
            return 'website';
        case 'facebook':
        case 'instagram':
        case 'tiktok':
        case 'twitter':
        case 'youtube':
        case 'linkedin':
            return 'social';
        default:
            return null;
    }
}