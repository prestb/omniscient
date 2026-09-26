<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('business_contacts', 'deleted_at')) {
            Schema::table('business_contacts', function (Blueprint $table) {
                $table->softDeletes()->after('updated_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('business_contacts', 'deleted_at')) {
            Schema::table('business_contacts', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};