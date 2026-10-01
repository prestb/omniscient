<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\HasPlanFeatures;


class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasPlanFeatures;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'status',
        'failed_login_attempts',
        'locked_until',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'role' => 'string',
        'status' => 'string',
        'failed_login_attempts' => 'integer',
    ];

    /**
     * Role Constants
     */
    const ROLE_USER = 'user'; // ✅ NEW: Regular customers
    const ROLE_ADMIN = 'admin';
    const ROLE_OWNER = 'owner';
    const ROLE_SUPER_ADMIN = 'super_admin';

    /**
     * Status Constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_SUSPENDED = 'suspended';

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN]);
    }

    /**
     * Check if user is a super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    /**
     * Check if user has admin/super admin access
     */
    public function hasAdminAccess(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN]);
    }

    /**
     * Check if user has a business dashboard
     */
    public function hasBusinessAccess(): bool
    {
        return $this->role === self::ROLE_OWNER || $this->hasAdminAccess();
    }

    /**
     * Check if user is an owner
     */
    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    /**
     * Check if user is a regular customer
     */
    public function isUser(): bool
    {
        return $this->role === self::ROLE_USER;
    }
    /**
     * Check if user account is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if user account is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if user account is suspended
     */
    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    /**
     * Scope for admin users
     */
    public function scopeAdmins($query)
    {
        return $query->whereIn('role', [self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN]);
    }

    /**
     * Scope for active users
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Get the businesses owned by the user
     */
    public function businesses()
    {
        return $this->hasMany(Business::class, 'owner_id');
    }

    /**
     * PHASE 9 — canonical Account → Listings relationship.
     *
     * A Listing is the discoverable entity owned by this account
     * ({@see \App\Models\Listing}). This is now genuinely DISTINCT from
     * {@see businesses()} (organizations): an account may own several
     * organizations and several standalone listings.
     *
     * Entitlements are account-scoped (subscription → plan → limits), NOT
     * per-listing.
     */
    public function listings()
    {
        return $this->hasMany(Listing::class, 'owner_id');
    }

    /**
     * Count of listings owned by this account (the quota metric).
     */
    public function listingsCount(): int
    {
        return $this->listings()->count();
    }

    public function hasBusiness()
    {
        return $this->businesses()->count() > 0;
    }


    // ============== NOTIFICATION METHODS ==============

    public function notifications()
    {
        return $this->morphMany(\App\Models\Notification::class, 'notifiable')->latest();
    }

    public function unreadNotifications()
    {
        return $this->notifications()->whereNull('read_at');
    }

    public function readNotifications()
    {
        return $this->notifications()->whereNotNull('read_at');
    }

    public function notificationPreferences()
    {
        return $this->hasMany(\App\Models\NotificationPreference::class);
    }

    public function getNotificationPreference($type)
    {
        $preference = $this->notificationPreferences()
            ->where('type', $type)
            ->first();

        if (!$preference) {
            return [
                'email' => true,
                'in_app' => true,
                'sms' => false,
                'push' => true, // ✅ Add push default
            ];
        }

        return [
            'email' => (bool) $preference->email,
            'in_app' => (bool) $preference->in_app,
            'sms' => (bool) $preference->sms,
            'push' => (bool) ($preference->push ?? true), // ✅ Add push
        ];
    }

    public function markAllNotificationsAsRead()
    {
        $this->unreadNotifications()->update(['read_at' => now()]);
        return $this;
    }

    public function getUnreadNotificationsCountAttribute()
    {
        return $this->unreadNotifications()->count();
    }


    /**
     * Send a notification to the user based on their preferences
     * (in-app + email via the app's own Notification model).
     *
     * ⚠️ Named `sendAppNotification` (not `notify`) to avoid colliding with
     *    Laravel's `Notifiable::notify()` contract — which is what
     *    MustVerifyEmail, password-reset, and other framework features
     *    rely on. `notify()` must keep the framework signature.
     */
    public function sendAppNotification(array $notification): self
    {
        $preferences = $this->getNotificationPreference($notification['type'] ?? 'general');

        if ($preferences['in_app']) {
            $this->notifications()->create([
                'type' => $notification['type'] ?? 'general',
                'data' => [
                    'title' => $notification['title'] ?? 'Notification',
                    'message' => $notification['message'] ?? '',
                    'action_url' => $notification['action_url'] ?? null,
                    'data' => $notification['data'] ?? [],
                ],
                'read_at' => null,
            ]);
        }

        if ($preferences['email']) {
            try {
                \Mail::to($this->email)->send(new \App\Mail\NotificationMail(
                    $notification['title'] ?? 'Notification',
                    $notification['message'] ?? '',
                    $notification['action_url'] ?? null
                ));
            } catch (\Exception $e) {
                \Log::error('Failed to send email notification: ' . $e->getMessage());
            }
        }

        return $this;
    }



    public function isLocked(): bool
    {
        if (!$this->locked_until) {
            return false;
        }
        return now()->lessThan($this->locked_until);
    }

    public function lockAccount($minutes = 15): void
    {
        $this->update([
            'locked_until' => now()->addMinutes($minutes),
            'status' => 'suspended',
        ]);
    }

    public function resetFailedAttempts(): void
    {
        $this->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);
    }

    // ============== SUBSCRIPTION RELATIONSHIPS ==============

    /**
     * Active subscription relationship.
     * ✅ Includes grace_period so business stays visible until suspension.
     */
    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->whereIn('status', [
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_EXPIRING_SOON,
                Subscription::STATUS_GRACE_PERIOD,
            ])
            ->latest('created_at');
    }

    public function latestSubscription()
    {
        return $this->hasOne(Subscription::class, 'owner_id')->latest();
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class)->latest();
    }


    /**
     * Get the user's current subscription plan details
     */
    public function getSubscriptionPlanAttribute()
    {
        $subscription = $this->active_subscription;
        return $subscription ? $subscription->plan : null;
    }



    /**
     * Get the user's subscription status
     */
    public function getSubscriptionStatusAttribute()
    {
        $subscription = $this->active_subscription;
        return $subscription ? $subscription->status : null;
    }


    // ============== SUBSCRIPTION LIMIT CHECKS ==============

    /**
     * Check if user can create a new business
     */
    public function canCreateBusiness()
    {
        // Super admin can create unlimited
        if ($this->role === 'super_admin') {
            return true;
        }

        $subscription = $this->active_subscription;

        // No active subscription
        if (!$subscription) {
            return false;
        }

        // Get plan limits
        $maxBusinesses = $subscription->plan->max_listings ?? 0;

        // Unlimited
        if ($maxBusinesses === -1) {
            return true;
        }

        // Count existing businesses
        $businessCount = Business::where('owner_id', $this->id)
            ->whereNotIn('status', ['deleted', 'rejected'])
            ->count();

        return $businessCount < $maxBusinesses;
    }

    /**
     * Get remaining business slots
     */
    public function getRemainingBusinessSlots()
    {
        if ($this->role === 'super_admin') {
            return PHP_INT_MAX;
        }

        $subscription = $this->active_subscription;
        if (!$subscription) {
            return 0;
        }

        $maxBusinesses = $subscription->plan->max_listings ?? 0;
        if ($maxBusinesses === -1) {
            return PHP_INT_MAX;
        }

        $businessCount = Business::where('owner_id', $this->id)
            ->whereNotIn('status', ['deleted', 'rejected'])
            ->count();

        return max(0, $maxBusinesses - $businessCount);
    }

    /**
     * Get current business count
     */
    public function getBusinessCount()
    {
        return Business::where('owner_id', $this->id)
            ->whereNotIn('status', ['deleted', 'rejected'])
            ->count();
    }

    /**
     * Get max businesses allowed by current plan
     */
    public function getMaxBusinesses()
    {
        if ($this->role === 'super_admin') {
            return PHP_INT_MAX;
        }

        $subscription = $this->activeSubscription;
        if (!$subscription) {
            return 0;
        }

        return $subscription->plan->max_listings ?? 0;
    }

    /**
     * Get subscription plan details
     */
    public function getSubscriptionPlanDetails()
    {
        $subscription = $this->active_subscription;
        if (!$subscription) {
            return null;
        }

        return [
            'plan_name' => $subscription->plan->name,
            'max_listings' => $subscription->plan->max_listings,
            'max_locations' => $subscription->plan->max_locations,
            'max_images' => $subscription->plan->max_images,
            'status' => $subscription->status,
            'status_label' => $subscription->status_label,
            'expires_at' => $subscription->end_date,
            'days_remaining' => $subscription->days_remaining,
        ];
    }


    /**
     * Get the active subscription for this user.
     * ✅ Includes active, expiring_soon, and grace_period states so the
     *    owner dashboard, banners, and public visibility checks still work
     *    during the pre-suspension window.
     *
     * ✅ Memoized per model instance to avoid N+1 in feature checks.
     */
    protected ?Subscription $memoizedActiveSubscription = null;
    protected bool $activeSubscriptionMemoized = false;

    public function getActiveSubscriptionAttribute()
    {
        if ($this->activeSubscriptionMemoized) {
            return $this->memoizedActiveSubscription;
        }

        // ✅ Prefer the loaded relation
        if ($this->relationLoaded('activeSubscription')) {
            $subscription = $this->getRelation('activeSubscription');
            if ($subscription && !$subscription->relationLoaded('plan')) {
                $subscription->load('plan');
            }
        } else {
            $subscription = Subscription::where('user_id', $this->id)
                ->whereIn('status', [
                    Subscription::STATUS_ACTIVE,
                    Subscription::STATUS_EXPIRING_SOON,
                    Subscription::STATUS_GRACE_PERIOD,
                ])
                ->with(['plan', 'business'])
                ->latest()
                ->first();
        }

        if ($subscription) {
            $subscription->duration_label;
            $subscription->formatted_price;
            $subscription->formatted_monthly_price;
            $subscription->days_remaining;
        }

        $this->memoizedActiveSubscription = $subscription;
        $this->activeSubscriptionMemoized = true;

        return $subscription;
    }

    /**
     * Get the active subscription directly (alias)
     */
    public function getActiveSubscription()
    {
        return $this->active_subscription;
    }



    /**
     * Check if user has an active subscription
     */
    public function getHasActiveSubscriptionAttribute()
    {
        return $this->active_subscription !== null;
    }


    public function pushSubscriptions()
    {
        return $this->hasMany(PushSubscription::class);
    }

    /**
     * Get push notification subscriptions for this user
     */
    public function getPushSubscriptionsAttribute()
    {
        return $this->pushSubscriptions()->pluck('endpoint')->toArray();
    }

    // ============== FAVORITES ==============

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function couponRedemptionTokens()
    {
        return $this->hasMany(CouponRedemptionToken::class);
    }

    public function couponRedemptions()
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function favoriteBusinesses()
    {
        return $this->belongsToMany(Business::class, 'favorites')
            ->withTimestamps()
            ->orderBy('favorites.created_at', 'desc');
    }

    public function hasFavorited(Business $business): bool
    {
        return $this->favorites()->where('business_id', $business->id)->exists();
    }

    public function getFavoritesCountAttribute(): int
    {
        return $this->favorites()->count();
    }
}