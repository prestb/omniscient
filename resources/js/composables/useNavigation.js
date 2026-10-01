// resources/js/composables/useNavigation.js
//
// Single source of truth for all navigation items, keyed by role.
// Desktop links and mobile bottom-tabs are both subsets of `items`.
// Owner/Admin/SuperAdmin also have `sections` for the sidebar.

export function useNavigation() {
    const items = {
        user: [
            { label: 'Home',          href: '/',                          icon: 'home' },
            { label: 'Directory',     href: '/directory',                 icon: 'search' },
            { label: 'Favorites',     href: '/favorites',                 icon: 'heart' },
            { label: 'My Account',    href: '/user/dashboard',            icon: 'user' },
            { label: 'List Business', href: '/owner/businesses/create',   icon: 'plus-briefcase' },
        ],
        owner: [
            { label: 'Dashboard',      href: '/owner/dashboard',          icon: 'grid' },
            { label: 'Businesses',     href: '/owner/businesses',         icon: 'briefcase' },
            // PHASE 11 / WAVE 1D — Reviews are Business-specific, so this points at the
        // owner's Businesses; the Business is chosen explicitly, never guessed.
        { label: 'Reviews',        href: '/owner/businesses',          icon: 'star' },
            { label: 'Coupons',        href: '/owner/coupons',            icon: 'ticket' },
            // PHASE 11 / WAVE 1D — Leads are Business-specific (see Reviews above).
        { label: 'Leads',          href: '/owner/businesses',          icon: 'inbox' },
            { label: 'Analytics',      href: '/owner/analytics',          icon: 'chart-bar' },
            { label: 'Usage',          href: '/owner/usage',              icon: 'list' },
            { label: 'Subscription',   href: '/owner/subscription',       icon: 'credit-card' },
            { label: 'Notifications',  href: '/owner/notifications',      icon: 'bell' },
        ],
        admin: [
            { label: 'Dashboard',      href: '/admin/dashboard',           icon: 'grid' },
            { label: 'Businesses',     href: '/admin/businesses',          icon: 'briefcase' },
            { label: 'Reviews',        href: '/admin/reviews',             icon: 'star' },
            { label: 'Owners',         href: '/admin/owners',              icon: 'user-group' },
            { label: 'Users',          href: '/admin/users',               icon: 'users' },
            { label: 'Subscriptions',  href: '/admin/subscriptions',       icon: 'credit-card' },
            { label: 'Payments',       href: '/admin/payments',            icon: 'banknotes' },
            { label: 'Plans',          href: '/admin/plans',               icon: 'package' },
            { label: 'Revenue',        href: '/admin/revenue',             icon: 'chart-bar' },
            { label: 'Categories',     href: '/admin/categories',          icon: 'tag' },
            { label: 'Locations',      href: '/admin/locations/countries', icon: 'map-pin' },
            { label: 'Contacts',       href: '/admin/contacts',            icon: 'inbox' },
            { label: 'Invitations',    href: '/admin/invitations',         icon: 'envelope' },
            { label: 'Notifications',  href: '/admin/notifications',       icon: 'bell' },
            { label: 'Settings',       href: '/admin/settings',            icon: 'cog' },
        ],
        super_admin: [
            { label: 'Dashboard',       href: '/admin/dashboard',           icon: 'grid' },
            { label: 'Super Dashboard', href: '/admin/super-dashboard',     icon: 'shield' },
            { label: 'Businesses',      href: '/admin/businesses',          icon: 'briefcase' },
            { label: 'Reviews',         href: '/admin/reviews',             icon: 'star' },
            { label: 'Owners',          href: '/admin/owners',              icon: 'user-group' },
            { label: 'Users',           href: '/admin/users',               icon: 'users' },
            { label: 'Subscriptions',   href: '/admin/subscriptions',       icon: 'credit-card' },
            { label: 'Payments',        href: '/admin/payments',            icon: 'banknotes' },
            { label: 'Plans',           href: '/admin/plans',               icon: 'package' },
            { label: 'Revenue',         href: '/admin/revenue',             icon: 'chart-bar' },
            { label: 'Categories',      href: '/admin/categories',          icon: 'tag' },
            { label: 'Locations',       href: '/admin/locations/countries', icon: 'map-pin' },
            { label: 'Contacts',        href: '/admin/contacts',            icon: 'inbox' },
            { label: 'Invitations',     href: '/admin/invitations',         icon: 'envelope' },
            { label: 'Notifications',   href: '/admin/notifications/scheduled',       icon: 'bell' },
            { label: 'Settings',        href: '/admin/settings',            icon: 'cog' },
        ],
    };

    // Sidebar sections (grouped)
    const sections = {
        owner: [
            {
                label: 'Overview',
                items: ['Dashboard', 'Analytics', 'Usage'],
            },
            {
                label: 'Business',
                items: ['Businesses', 'Reviews', 'Coupons', 'Leads'],
            },
            {
                label: 'Account',
                items: ['Subscription', 'Notifications'],
            },
        ],
        admin: [
            {
                label: 'Overview',
                items: ['Dashboard', 'Revenue'],
            },
            {
                label: 'Moderation',
                items: ['Businesses', 'Reviews', 'Contacts'],
            },
            {
                label: 'Users & Billing',
                items: ['Users', 'Owners', 'Subscriptions', 'Payments', 'Plans'],
            },
            {
                label: 'Content',
                items: ['Categories', 'Locations', 'Invitations'],
            },
            {
                label: 'System',
                items: ['Notifications', 'Settings'],
            },
        ],
        super_admin: [
            {
                label: 'Overview',
                items: ['Dashboard', 'Super Dashboard', 'Revenue'],
            },
            {
                label: 'Moderation',
                items: ['Businesses', 'Reviews', 'Contacts'],
            },
            {
                label: 'Users & Billing',
                items: ['Users', 'Owners', 'Subscriptions', 'Payments', 'Plans'],
            },
            {
                label: 'Content',
                items: ['Categories', 'Locations', 'Invitations'],
            },
            {
                label: 'System',
                items: ['Notifications', 'Settings'],
            },
        ],
    };

    // Which labels show up in the desktop horizontal nav.
    // Owner/Admin/SuperAdmin now use the sidebar — no horizontal items needed.
    const desktopLabels = {
        user:        ['Home', 'Directory', 'Favorites', 'My Account'],
        owner:       [],
        admin:       [],
        super_admin: [],
    };

    // Which labels show up in the mobile bottom tab bar (exactly 5 — Apple/Google recommended max)
    const mobileLabels = {
        user:        ['Home', 'Directory', 'Favorites', 'My Account', 'List Business'],
        owner:       ['Dashboard', 'Businesses', 'Reviews', 'Leads', 'Usage'],
        admin:       ['Dashboard', 'Businesses', 'Reviews', 'Contacts', 'Invitations'],
        super_admin: ['Dashboard', 'Super Dashboard', 'Businesses', 'Revenue', 'Plans'],
    };

    /**
     * Get the full item list for a role.
     */
    const itemsFor = (role) => items[role] || [];

    /**
     * Get the desktop subset for a role.
     */
    const desktopItemsFor = (role) => {
        const labels = desktopLabels[role] || [];
        if (labels.length === 0) return [];
        return itemsFor(role).filter((i) => labels.includes(i.label));
    };

    /**
     * Get the mobile subset for a role.
     */
    const mobileItemsFor = (role) => {
        const labels = mobileLabels[role] || [];
        return itemsFor(role).filter((i) => labels.includes(i.label));
    };

    /**
     * Get grouped sidebar sections for owner/admin/super.
     * Returns [] for roles without a sidebar config.
     */
    const sidebarSectionsFor = (role) => {
        const roleSections = sections[role];
        if (!roleSections) return [];

        const roleItems = itemsFor(role);

        return roleSections.map((section) => ({
            label: section.label,
            items: section.items
                .map((label) => roleItems.find((i) => i.label === label))
                .filter(Boolean), // drop any labels not in items
        })).filter((s) => s.items.length > 0); // drop empty sections
    };

    /**
     * Whether a role should use the sidebar layout.
     */
    const usesSidebar = (role) => role === 'owner' || role === 'admin' || role === 'super_admin';

    /**
     * Determine whether a nav item is active, given the current URL.
     * Matches exact path or prefix (for nested routes).
     * The root path "/" only matches exactly.
     */
    const isItemActive = (item, currentUrl) => {
        if (!item?.href) return false;
        if (item.href === '/') return currentUrl === '/';
        return currentUrl === item.href || currentUrl.startsWith(item.href + '/') || currentUrl.startsWith(item.href + '?');
    };

    return {
        items,
        sections,
        desktopLabels,
        mobileLabels,
        itemsFor,
        desktopItemsFor,
        mobileItemsFor,
        sidebarSectionsFor,
        usesSidebar,
        isItemActive,
    };
}