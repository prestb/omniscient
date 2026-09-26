<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            
            // Content
            $table->string('title');
            $table->text('message');
            $table->string('action_url')->nullable();
            $table->string('type')->default('admin_notification');
            
            // Recipients
            $table->string('recipient_type'); // all, admins, owners, custom
            $table->json('recipient_ids')->nullable(); // for custom selection
            $table->integer('recipient_count')->default(0); // snapshot at send time
            
            // Delivery options
            $table->boolean('send_push')->default(true);
            $table->boolean('save_database')->default(true);
            
            // Scheduling
            $table->timestamp('scheduled_at')->index();
            $table->string('status')->default('pending')->index(); // pending, processing, sent, failed, cancelled
            $table->timestamp('sent_at')->nullable();
            $table->text('failure_reason')->nullable();
            
            // Stats
            $table->integer('sent_count')->default(0);
            $table->integer('push_sent_count')->default(0);
            
            // Recurring (for future)
            $table->string('recurrence')->nullable(); // daily, weekly, monthly
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_notifications');
    }
};