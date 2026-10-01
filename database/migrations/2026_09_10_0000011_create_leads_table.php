<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
                        $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            // PHASE 9/10 — a lead is attributed to a LISTING (added in the
            // listings migration); there is no `branch_id` any more.

            
            // Lead source
            $table->string('source')->default('contact_form'); // contact_form, whatsapp, phone, booking
            
            // Contact info (from the visitor)
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            
            // Message
            $table->string('subject')->nullable();
            $table->text('message');
            
            // Metadata
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable();
            
            // Status
            $table->enum('status', ['new', 'read', 'replied', 'archived'])->default('new');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->text('owner_notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('business_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};