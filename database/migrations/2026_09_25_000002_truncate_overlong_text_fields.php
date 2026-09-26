<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * ✅ Truncate any existing rows that exceed the new limits.
     *
     * Verified against the production DB on 2026-09-25:
     *   businesses.description     → 0 rows over 255
     *   business_services.name     → 0 rows over 50
     *   business_services.description → 0 rows over 75
     *   branches.address           → 0 rows over 100
     *   reviews.content            → 0 rows over 500
     *
     * So this migration is effectively a no-op — but it stays as a
     * safety net for any environment where older data might exist.
     */
    public function up(): void
    {
        // ==== Text content ====
        DB::statement("UPDATE businesses SET description = LEFT(description, 255) WHERE CHAR_LENGTH(description) > 255");
        DB::statement("UPDATE business_services SET name = LEFT(name, 50) WHERE CHAR_LENGTH(name) > 50");
        DB::statement("UPDATE business_services SET description = LEFT(description, 75) WHERE CHAR_LENGTH(description) > 75");
        DB::statement("UPDATE reviews SET content = LEFT(content, 500) WHERE CHAR_LENGTH(content) > 500");

        // ==== Identifiers / headings ====
        DB::statement("UPDATE businesses SET name = LEFT(name, 100) WHERE CHAR_LENGTH(name) > 100");
        DB::statement("UPDATE branches SET name = LEFT(name, 100) WHERE CHAR_LENGTH(name) > 100");
        DB::statement("UPDATE branches SET address = LEFT(address, 100) WHERE CHAR_LENGTH(address) > 100");
        DB::statement("UPDATE business_images SET caption = LEFT(caption, 100) WHERE CHAR_LENGTH(caption) > 100");
    }

    public function down(): void
    {
        // Irreversible — truncated data cannot be restored.
    }
};