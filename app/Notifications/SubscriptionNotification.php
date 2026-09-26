<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $subscription;
    protected $type;

    public function __construct(Subscription $subscription, $type)
    {
        $this->subscription = $subscription;
        $this->type = $type;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $messages = [
            'active' => [
                'subject' => 'Subscription Activated',
                'greeting' => 'Your subscription is now active!',
                'line' => 'Your subscription to ' . $this->subscription->plan->name . ' has been activated.',
            ],
            'expiring' => [
                'subject' => 'Subscription Expiring Soon',
                'greeting' => 'Your subscription is expiring soon!',
                'line' => 'Your subscription to ' . $this->subscription->plan->name . ' will expire on ' . $this->subscription->end_date->format('F j, Y') . '. Please renew to continue enjoying our services.',
            ],
            'expired' => [
                'subject' => 'Subscription Expired',
                'greeting' => 'Your subscription has expired!',
                'line' => 'Your subscription to ' . $this->subscription->plan->name . ' has expired. Please renew to reactivate your listing.',
            ],
            'grace_period' => [
                'subject' => 'Subscription in Grace Period',
                'greeting' => 'Your subscription is in grace period!',
                'line' => 'Your subscription has expired and is now in a grace period. Please renew within 7 days to avoid suspension.',
            ],
            'suspended' => [
                'subject' => 'Subscription Suspended',
                'greeting' => 'Your subscription has been suspended!',
                'line' => 'Your subscription has been suspended. Please contact support to reactivate your listing.',
            ],
        ];

        $message = $messages[$this->type] ?? $messages['active'];

        return (new MailMessage)
            ->subject($message['subject'])
            ->greeting($message['greeting'])
            ->line($message['line'])
            ->action('View Subscription', route('owner.subscription.index'))
            ->line('If you have any questions, please contact our support team.')
            ->salutation('Omniscient Team');
    }

    public function toDatabase($notifiable)
    {
        $messages = [
            'active' => 'Your subscription to ' . $this->subscription->plan->name . ' has been activated.',
            'expiring' => 'Your subscription is expiring soon. Please renew to continue enjoying our services.',
            'expired' => 'Your subscription has expired. Please renew to reactivate your listing.',
            'grace_period' => 'Your subscription is in grace period. Please renew within 7 days.',
            'suspended' => 'Your subscription has been suspended. Please contact support.',
        ];

        return [
            'type' => 'subscription_' . $this->type,
            'title' => ucfirst(str_replace('_', ' ', $this->type)) . ' Subscription',
            'message' => $messages[$this->type] ?? 'Subscription status updated.',
            'data' => [
                'subscription_id' => $this->subscription->id,
                'plan_name' => $this->subscription->plan->name,
                'status' => $this->subscription->status,
            ],
            'action_url' => route('owner.subscription.index'),
        ];
    }
}