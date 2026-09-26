// resources/js/composables/useDirectoryFilters.js
//
// ✅ Persists directory filters + view mode to cookies so the SERVER can
//    read them on a bare /directory request and redirect to a canonical
//    filtered URL (no flash, no double render).
//
// Cookies (not localStorage) because the server needs to read them on the
// initial request. Names are versioned so the shape can change later.

const FILTERS_COOKIE = 'directory_filters_v1';
const VIEW_COOKIE = 'directory_view_mode_v1';

const ONE_YEAR_SECONDS = 60 * 60 * 24 * 365;

// Filter keys that are part of the "filter set" (everything except `view`).
const FILTER_KEYS = [
    'search',
    'category',
    'country_id',
    'region_id',
    'city_id',
    'open_now',
    'featured',
    'verified',
    'min_rating',
    'has_photos',
    'has_whatsapp',
    'sort',
];

// ============== LOW-LEVEL COOKIE HELPERS ==============

function readCookie(name) {
    if (typeof document === 'undefined') return null;
    const match = document.cookie.match(
        new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1') + '=([^;]*)')
    );
    return match ? decodeURIComponent(match[1]) : null;
}

function writeCookie(name, value, maxAgeSeconds = ONE_YEAR_SECONDS) {
    if (typeof document === 'undefined') return;
    const encoded = encodeURIComponent(value);
    const secure = window.location.protocol === 'https:' ? '; Secure' : '';
    document.cookie =
        `${name}=${encoded}; Max-Age=${maxAgeSeconds}; Path=/; SameSite=Lax${secure}`;
}

function deleteCookie(name) {
    if (typeof document === 'undefined') return;
    document.cookie =
        `${name}=; Max-Age=0; Path=/; SameSite=Lax`;
}

// ============== FILTERS ==============

/**
 * Read stored filters from the cookie.
 * Returns an object with only non-empty keys, or null if nothing stored.
 */
export function readDirectoryFilters() {
    try {
        const raw = readCookie(FILTERS_COOKIE);
        if (!raw) return null;

        const parsed = JSON.parse(raw);
        if (!parsed || typeof parsed !== 'object') return null;

        const cleaned = {};
        Object.keys(parsed).forEach((k) => {
            if (!FILTER_KEYS.includes(k)) return;
            const v = parsed[k];
            if (v === null || v === undefined || v === '' || v === false || v === 0 || v === '0') return;
            cleaned[k] = v;
        });

        return Object.keys(cleaned).length > 0 ? cleaned : null;
    } catch (e) {
        console.warn('[useDirectoryFilters] Failed to read filters:', e);
        return null;
    }
}

/**
 * Persist filters to the cookie.
 * Accepts the raw params object (may contain empties — they get stripped).
 * Passing an empty object effectively clears the cookie.
 */
export function writeDirectoryFilters(filters) {
    try {
        const cleaned = {};
        FILTER_KEYS.forEach((k) => {
            if (!filters || filters[k] === undefined || filters[k] === null) return;
            const v = filters[k];
            if (v === '' || v === false || v === 0 || v === '0' || v === 'false') return;
            cleaned[k] = v;
        });

        if (Object.keys(cleaned).length === 0) {
            deleteCookie(FILTERS_COOKIE);
        } else {
            writeCookie(FILTERS_COOKIE, JSON.stringify(cleaned));
        }
    } catch (e) {
        console.warn('[useDirectoryFilters] Failed to write filters:', e);
    }
}

/**
 * Clear all stored filters.
 */
export function clearDirectoryFilters() {
    deleteCookie(FILTERS_COOKIE);
}

// ============== VIEW MODE ==============

/**
 * Read the last-used view mode (list | map).
 */
export function readDirectoryViewMode() {
    const v = readCookie(VIEW_COOKIE);
    return v === 'map' || v === 'list' ? v : null;
}

/**
 * Persist the current view mode.
 */
export function writeDirectoryViewMode(mode) {
    if (mode === 'map' || mode === 'list') {
        writeCookie(VIEW_COOKIE, mode);
    }
}