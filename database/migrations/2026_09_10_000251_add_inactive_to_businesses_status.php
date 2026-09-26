<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL: modify ENUM to include 'inactive'
        DB::statement("
            ALTER TABLE businesses
            MODIFY COLUMN status 
            ENUM('draft', 'submitted', 'approved', 'published', 'inactive', 'rejected', 'suspended')
            NOT NULL DEFAULT 'draft'
        ");
    }

    public function down(): void
    {
        // Move any 'inactive' rows to 'approved' first
        DB::table('businesses')->where('status', 'inactive')->update(['status' => 'approved']);

        DB::statement("
            ALTER TABLE businesses
            MODIFY COLUMN status 
            ENUM('draft', 'submitted', 'approved', 'published', 'rejected', 'suspended')
            NOT NULL DEFAULT 'draft'
        ");
    }
};