<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Helpers\NotificationHelper;
use Illuminate\Console\Command;

class TestNotification extends Command
{
    protected $signature = 'test:notification';
    protected $description = 'Test notification system';

    public function handle()
    {
        $this->info('Testing notification system...');

        $user = User::where('email', 'owner@omniscient.com')->first();
        
        if (!$user) {
            $this->error('User not found');
            return 1;
        }

        $this->info('Sending notification to: ' . $user->email);

        $result = NotificationHelper::send(
            $user,
            'Test Notification',
            'This is a test notification to verify the system is working.',
            '/owner/dashboard',
            ['test' => true],
            'general'
        );

        if ($result) {
            $this->info('✅ Notification created successfully!');
            $this->info('Notification ID: ' . $result->id);
        } else {
            $this->error('❌ Failed to create notification');
        }

        // Check if notification exists in database
        $count = \App\Models\Notification::where('notifiable_id', $user->id)->count();
        $this->info('Total notifications for user: ' . $count);

        return 0;
    }
}