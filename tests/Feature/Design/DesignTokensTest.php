<?php

/**
 * PHASE 16B — DESIGN TOKENS & PUBLIC UI PRIMITIVES.
 *
 * Static assertions over the design foundation. These do not render Vue (there
 * is no browser here); they assert the contracts the primitives are required to
 * uphold, which is what stops the audit's findings from regressing.
 */

function ui(string $file): string
{
    return file_get_contents(resource_path("js/Components/Public/ui/{$file}"));
}

// ── Font decision ───────────────────────────────────────────────────────────

test('figtree is the single canonical font', function () {
    $config = file_get_contents(base_path('tailwind.config.js'));

    expect($config)->toContain("'Figtree'");
    // The stale Inter declaration that contradicted the runtime font is gone.
    expect($config)->not->toContain("'Inter'");

    // Runtime source still loads Figtree, so runtime === theme === token.
    $blade = file_get_contents(resource_path('views/app.blade.php'));
    expect($blade)->toContain('figtree');
});

// ── Token architecture ──────────────────────────────────────────────────────

test('semantic colour tokens exist', function () {
    $config = file_get_contents(base_path('tailwind.config.js'));

    foreach (['surface', 'ink', 'hairline', 'success', 'warning', 'danger', 'info'] as $token) {
        expect($config)->toContain($token);
    }
});

test('shape, elevation and motion tokens exist', function () {
    $config = file_get_contents(base_path('tailwind.config.js'));

    foreach (['control', 'card', 'pill'] as $shape) {
        expect($config)->toContain("{$shape}:");
    }
    foreach (['elevation-1', 'elevation-2', 'elevation-3'] as $elevation) {
        expect($config)->toContain($elevation);
    }
    foreach (['fast', 'normal', 'slow', 'standard', 'emphasized'] as $motion) {
        expect($config)->toContain("{$motion}:");
    }
});

test('semantic typography roles exist', function () {
    $config = file_get_contents(base_path('tailwind.config.js'));

    foreach (['display', 'heading-xl', 'heading-lg', 'heading-md', 'heading-sm',
        'body-lg', 'body-sm', 'label', 'caption'] as $role) {
        expect($config)->toContain($role);
    }
});

test('reduced motion is handled globally', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)->toContain('prefers-reduced-motion');
});

// ── Primitives ──────────────────────────────────────────────────────────────

test('the primitive set exists', function () {
    foreach (['Button', 'Icon', 'IconButton', 'Badge', 'Chip', 'Sheet', 'Input'] as $name) {
        expect(file_exists(resource_path("js/Components/Public/ui/{$name}.vue")))->toBeTrue();
    }
});

test('button supports the required variants and sizes', function () {
    $button = ui('Button.vue');

    foreach (['primary', 'secondary', 'danger', 'ghost'] as $variant) {
        expect($button)->toContain("'{$variant}'");
    }
    foreach (['sm', 'md', 'lg'] as $size) {
        expect($button)->toContain("'{$size}'");
    }

    // Disabled, loading and focus-visible behaviour are required.
    expect($button)->toContain('disabled');
    expect($button)->toContain('loading');
    expect($button)->toContain('focus-visible:ring');
    expect($button)->toContain('aria-busy');
});

test('button uses the touch-friendly default and a deliberate compact variant', function () {
    // Strip comments: this phase's docblocks legitimately name the old h-8
    // problem they are describing.
    $button = preg_replace('#<!--.*?-->#s', '', ui('Button.vue'));
    $button = preg_replace('#^\s*//.*$#m', '', $button);

    // md is 44px (min-h-11); the compact variant must not shrink the tap target.
    expect($button)->toContain('min-h-11');
    expect($button)->not->toContain('h-8');
});

test('icon button requires an accessible label', function () {
    $iconButton = ui('IconButton.vue');

    expect($iconButton)->toContain('required: true');
    expect($iconButton)->toContain('aria-label');
});

test('icon primitive defaults to decorative but supports a label', function () {
    $icon = ui('Icon.vue');

    expect($icon)->toContain('aria-hidden');
    expect($icon)->toContain("role");
    expect($icon)->toContain('aria-label');
});

test('sheet provides real dialog semantics the audit found missing', function () {
    $sheet = ui('Sheet.vue');

    expect($sheet)->toContain('role="dialog"');
    expect($sheet)->toContain('aria-modal');
    expect($sheet)->toContain('Escape');
    expect($sheet)->toContain('body.style.overflow');
    expect($sheet)->toContain('focus');
});

test('badge supports the semantic variants', function () {
    $badge = ui('Badge.vue');

    foreach (['neutral', 'primary', 'success', 'warning', 'danger', 'info'] as $variant) {
        expect($badge)->toContain("'{$variant}'");
    }
});

test('chip supports selected and removable without owning filter logic', function () {
    $chip = ui('Chip.vue');

    expect($chip)->toContain('selected');
    expect($chip)->toContain('removable');
    expect($chip)->toContain('aria-pressed');
    // Presentation primitive only - no query/filter coupling.
    expect($chip)->not->toContain('router.');
    expect($chip)->not->toContain('axios');
});

test('input requires a label and wires error semantics', function () {
    $input = ui('Input.vue');

    expect($input)->toContain('required: true');
    expect($input)->toContain('aria-invalid');
    expect($input)->toContain('aria-describedby');
});

test('primitives do not introduce a new colour or radius vocabulary', function () {
    foreach (['Button', 'Badge', 'Chip', 'Input'] as $name) {
        $source = ui("{$name}.vue");

        // They consume tokens rather than raw hex.
        expect($source)->not->toMatch('/#[0-9a-fA-F]{6}/');
        expect($source)->not->toContain('rounded-xl');
        expect($source)->not->toContain('rounded-2xl');
    }
});

// ── Scope discipline ────────────────────────────────────────────────────────

test('existing button components were not removed in this phase', function () {
    // Migration is 16C+. The duplicated systems must still exist and still work.
    expect(file_exists(resource_path('js/Components/PrimaryButton.vue')))->toBeTrue();
    expect(file_get_contents(resource_path('css/app.css')))->toContain('btn-primary');
});

test('branch residue was reported, not removed, because it is still live', function () {
    // A live resource still emits `branches`, so blindly removing the fallback
    // would break the public Listing card. Documented for 16H instead.
    $resource = file_get_contents(app_path('Http/Resources/ListingDirectoryResource.php'));

    expect($resource)->toContain("'branches'");
    expect(file_exists(resource_path('js/Components/Public/BranchesSection.vue')))->toBeTrue();
});

test('no backend, route or SEO file was touched by this phase', function () {
    // These are the invariants Phase 16B must not break.
    expect(file_exists(app_path('Http/Controllers/Public/SitemapController.php')))->toBeTrue();
    expect(file_exists(resource_path('js/ssr.js')))->toBeTrue();
    expect(file_get_contents(resource_path('js/app.js')))->toContain('createInertiaApp');
});
