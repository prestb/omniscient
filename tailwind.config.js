// tailwind.config.js
//
// PHASE 16B — DESIGN TOKENS.
//
// This file becomes the single source of truth for the public design system.
// It does not replace Tailwind: it names the decisions Tailwind was previously
// expressing ad hoc (radii, shadows, durations, semantic colour).
//
// Existing raw Tailwind utilities continue to work. Migration of existing pages
// happens in 16C onward, not here.

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.vue',
        './resources/**/*.js',
    ],
    theme: {
        extend: {
            // ── Typography ──────────────────────────────────────────────────
            // PHASE 16B font decision: FIGTREE is canonical.
            //
            // Phase 16A found Tailwind declaring Inter while app.blade.php
            // loads Figtree, so the app rendered a font the config denied.
            // Figtree is the font actually rendered, is loaded deliberately
            // from a single source, and no local assets exist for either
            // family. Making Figtree canonical restores one intentional
            // contract; switching to Inter would be an unrequested visual
            // change justified only by a stale config string.
            fontFamily: {
                sans: ['Figtree', 'system-ui', 'sans-serif'],
            },

            // Semantic type roles. Sizes/weights are the existing visual
            // language, named rather than re-invented.
            fontSize: {
                display: ['2.25rem', { lineHeight: '2.5rem', fontWeight: '700', letterSpacing: '-0.02em' }],
                'heading-xl': ['1.875rem', { lineHeight: '2.25rem', fontWeight: '700', letterSpacing: '-0.02em' }],
                'heading-lg': ['1.5rem', { lineHeight: '2rem', fontWeight: '700', letterSpacing: '-0.01em' }],
                'heading-md': ['1.125rem', { lineHeight: '1.75rem', fontWeight: '600' }],
                'heading-sm': ['0.875rem', { lineHeight: '1.25rem', fontWeight: '600' }],
                'body-lg': ['1rem', { lineHeight: '1.6rem' }],
                'body': ['0.875rem', { lineHeight: '1.4rem' }],
                'body-sm': ['0.8125rem', { lineHeight: '1.25rem' }],
                'label': ['0.6875rem', { lineHeight: '1rem', fontWeight: '700', letterSpacing: '0.08em' }],
                'caption': ['0.75rem', { lineHeight: '1rem' }],
            },

            // ── Color ───────────────────────────────────────────────────────
            colors: {
                // Existing brand family, retained deliberately.
                primary: {
                    50: '#f0f9ff',
                    100: '#e0f2fe',
                    200: '#bae6fd',
                    300: '#7dd3fc',
                    400: '#38bdf8',
                    500: '#0ea5e9',
                    600: '#0284c7',
                    700: '#0369a1',
                    800: '#075985',
                    900: '#0c4a6e',
                },

                // Semantic aliases. Values are the Tailwind colours already in
                // use across the public UI, so consuming these is not a visual
                // change — it is naming what was already there.
                surface: {
                    DEFAULT: '#ffffff',
                    raised: '#ffffff',
                    muted: '#f9fafb',
                },
                ink: {
                    DEFAULT: '#111827',
                    muted: '#6b7280',
                    subtle: '#9ca3af',
                },
                hairline: {
                    DEFAULT: '#e5e7eb',
                    muted: '#f3f4f6',
                },
                success: { DEFAULT: '#059669', soft: '#ecfdf5' },
                warning: { DEFAULT: '#d97706', soft: '#fffbeb' },
                danger: { DEFAULT: '#dc2626', soft: '#fef2f2' },
                info: { DEFAULT: '#0284c7', soft: '#eff6ff' },
            },

            // ── Shape ───────────────────────────────────────────────────────
            // Collapses the four unmanaged radii into three named levels.
            borderRadius: {
                control: '0.5rem',   // inputs, small buttons
                card: '1rem',        // cards, panels, sheets
                pill: '9999px',      // chips, badges, avatars
            },

            // ── Elevation ───────────────────────────────────────────────────
            boxShadow: {
                'elevation-1': '0 1px 2px 0 rgb(0 0 0 / 0.05)',
                'elevation-2': '0 4px 6px -1px rgb(0 0 0 / 0.08), 0 2px 4px -2px rgb(0 0 0 / 0.06)',
                'elevation-3': '0 10px 15px -3px rgb(0 0 0 / 0.10), 0 4px 6px -4px rgb(0 0 0 / 0.06)',
            },

            // ── Motion ──────────────────────────────────────────────────────
            transitionDuration: {
                fast: '120ms',
                normal: '200ms',
                slow: '320ms',
            },
            transitionTimingFunction: {
                standard: 'cubic-bezier(0.4, 0, 0.2, 1)',
                emphasized: 'cubic-bezier(0.22, 1, 0.36, 1)',
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
    ],
};
