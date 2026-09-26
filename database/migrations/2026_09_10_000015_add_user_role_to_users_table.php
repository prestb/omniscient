<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ✅ Update the ENUM to include 'user'
        // MySQL syntax: ALTER TABLE users MODIFY role ENUM(...) DEFAULT 'user'
        DB::statement("
            ALTER TABLE users 
            MODIFY COLUMN role 
            ENUM('user', 'owner', 'admin', 'super_admin') 
            NOT NULL DEFAULT 'user'
        ");

        // ✅ Change default status from 'pending' to 'active'
        // Regular users don't need approval
        DB::statement("
            ALTER TABLE users 
            MODIFY COLUMN status 
            ENUM('pending', 'active', 'suspended') 
            NOT NULL DEFAULT 'active'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE users 
            MODIFY COLUMN role 
            ENUM('admin', 'owner', 'super_admin') 
            NOT NULL DEFAULT 'owner'
        ");

        DB::statement("
            ALTER TABLE users 
            MODIFY COLUMN status 
            ENUM('pending', 'active', 'suspended') 
            NOT NULL DEFAULT 'pending'
        ");
    }
};