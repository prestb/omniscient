<template>
    <div class="relative inline-block" ref="dropdownContainer">
        <!-- Bell Button -->
        <button @click="toggleDropdown"
            class="relative p-2 rounded-xl text-gray-500 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-800 transition-colors focus:outline-none"
            aria-label="Notifications">
            <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <span v-if="count > 0"
                class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center leading-none border-2 border-white dark:border-gray-900 shadow-md">
                {{ displayCount }}
            </span>
        </button>

        <!-- Dropdown -->
        <Teleport to="body">
            <Transition enter-active-class="transition duration-150 ease-out" enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-100 ease-in"
                leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                <div v-if="isOpen"
                    class="fixed z-50 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl shadow-gray-300/40 dark:shadow-black/40 border border-gray-100 dark:border-gray-700 overflow-hidden origin-top-right"
                    :style="dropdownStyle" @click.stop>

                    <!-- Header -->
                    <div
                        class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Notifications
                            </h3>
                            <span v-if="count > 0"
                                class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1.5 text-[10px] font-bold bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-300 rounded-full">
                                {{ displayCount }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <button v-if="unreadCount > 0" @click.stop="markAllAsRead"
                                class="text-xs text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 font-semibold whitespace-nowrap transition-colors">
                                Mark all
                            </button>
                            <a :href="allNotificationsRoute" @click.stop
                                class="text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 font-medium whitespace-nowrap transition-colors">
                                View all
                            </a>
                        </div>
                    </div>

                    <!-- Loading -->
                    <div v-if="loading" class="flex items-center justify-center py-10">
                        <svg class="animate-spin h-6 w-6 text-primary-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                    </div>

                    <!-- Empty -->
                    <div v-else-if="notifications.length === 0"
                        class="flex flex-col items-center justify-center py-10 px-4">
                        <div
                            class="w-14 h-14 bg-gradient-to-br from-gray-100 to-gray-50 dark:from-gray-700 dark:to-gray-800 rounded-2xl flex items-center justify-center mb-3 shadow-sm">
                            <svg class="w-6 h-6 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">No notifications</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">You're all caught up!</p>
                    </div>

                    <!-- List -->
                    <div v-else class="overflow-y-auto max-h-[60vh] sm:max-h-[400px]">
                        <div v-for="notification in notifications" :key="notification.id"
                            class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer border-b border-gray-100 dark:border-gray-700 last:border-0"
                            :class="!notification.read_at
                                ? 'bg-primary-50/40 dark:bg-primary-900/10'
                                : 'bg-white dark:bg-gray-800'" @click.stop="handleNotificationClick(notification)">
                            <div class="flex items-start gap-3">
                                <!-- Icon -->
                                <div class="flex-shrink-0 w-9 h-9 rounded-xl flex items-center justify-center text-base"
                                    :class="!notification.read_at
                                        ? 'bg-white dark:bg-gray-800 shadow-sm'
                                        : 'bg-gray-50 dark:bg-gray-700/50'">
                                    <span>{{ getIcon(notification.type) }}</span>
                                </div>

                                <!-- Content -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-2">
                                        <p
                                            class="text-sm font-semibold text-gray-900 dark:text-white truncate tracking-tight">
                                            {{ notification.data.title || 'Notification' }}
                                        </p>
                                        <span
                                            class="text-[10px] text-gray-400 dark:text-gray-500 flex-shrink-0 ml-1 font-medium">
                                            {{ formatTime(notification.created_at) }}
                                        </span>
                                    </div>
                                    <p
                                        class="text-sm text-gray-600 dark:text-gray-300 mt-0.5 break-words line-clamp-3 sm:line-clamp-2 leading-relaxed">
                                        {{ notification.data.message }}
                                    </p>
                                    <div v-if="!notification.read_at" class="mt-1.5">
                                        <span
                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300 rounded-full">
                                            <span class="w-1 h-1 rounded-full bg-primary-500 animate-pulse"></span>
                                            New
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div
                        class="px-4 py-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-center">
                        <a :href="allNotificationsRoute" @click.stop
                            class="inline-flex items-center gap-1.5 text-xs text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 font-semibold transition-colors">
                            View all notifications
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
    import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
    import axios from 'axios';

    const props = defineProps({
        route: {
            type: String,
            required: true
        },
        allNotificationsRoute: {
            type: String,
            required: true
        },
        initialCount: {
            type: Number,
            default: 0
        }
    });

    const emit = defineEmits(['count-updated']);

    const dropdownContainer = ref(null);
    const isOpen = ref(false);
    const loading = ref(false);
    const notifications = ref([]);
    const count = ref(props.initialCount);
    const unreadCount = ref(props.initialCount);
    const dropdownStyle = ref({});

    const displayCount = computed(() => {
        if (count.value > 99) return '99+';
        return count.value;
    });

    const toggleDropdown = () => {
        if (isOpen.value) {
            closeDropdown();
        } else {
            openDropdown();
        }
    };

    const openDropdown = () => {
        isOpen.value = true;
        loadNotifications();
        document.body.style.overflow = 'hidden';
        nextTick(() => {
            positionDropdown();
        });
    };

    const closeDropdown = () => {
        isOpen.value = false;
        document.body.style.overflow = '';
    };

    const positionDropdown = () => {
        if (!dropdownContainer.value) return;

        const buttonRect = dropdownContainer.value.getBoundingClientRect();
        const screenWidth = window.innerWidth;
        const screenHeight = window.innerHeight;

        let width = 380;
        if (screenWidth < 640) {
            width = screenWidth - 32;
        } else if (screenWidth < 768) {
            width = Math.min(380, screenWidth - 48);
        }

        let left = buttonRect.right - width;
        let top = buttonRect.bottom + 8;

        if (left < 8) left = 8;
        if (left + width > screenWidth - 8) {
            left = screenWidth - width - 8;
        }

        const dropdownHeight = Math.min(screenHeight * 0.6, 500);
        if (top + dropdownHeight > screenHeight - 16) {
            top = buttonRect.top - dropdownHeight - 8;
            if (top < 8) top = 8;
        }

        dropdownStyle.value = {
            width: width + 'px',
            left: left + 'px',
            top: top + 'px',
            maxHeight: dropdownHeight + 'px',
            minWidth: screenWidth < 640 ? 'auto' : '320px',
        };
    };

    const loadNotifications = async () => {
        if (loading.value) return;

        loading.value = true;
        try {
            const response = await axios.get(props.route);
            notifications.value = response.data.data || [];

            // ✅ The badge tracks UNREAD, not total. Previously this read
            //    `total`, which caused the badge to jump to the total count
            //    every time the dropdown opened — and stay there until a
            //    full page refresh restored it from the layout's poll.
            unreadCount.value = response.data.unread_count || 0;
            count.value = unreadCount.value;
            emit('count-updated', count.value);
        } catch (error) {
            console.error('Error loading notifications:', error);
        } finally {
            loading.value = false;
        }
    };

    const handleNotificationClick = (notification) => {
        if (!notification.read_at) {
            markAsRead(notification.id);
        }
        closeDropdown();
        if (notification.data.action_url) {
            window.location.href = notification.data.action_url;
        }
    };

    const markAsRead = (notificationId) => {
        axios.post(`${props.allNotificationsRoute}/${notificationId}/mark-as-read`)
            .then(() => {
                const notification = notifications.value.find(n => n.id === notificationId);
                if (notification) {
                    notification.read_at = new Date().toISOString();
                    unreadCount.value = Math.max(0, unreadCount.value - 1);
                    count.value = Math.max(0, count.value - 1);
                    emit('count-updated', count.value);
                }
            })
            .catch(error => {
                console.error('Error marking notification as read:', error);
            });
    };

    const markAllAsRead = () => {
        axios.post(`${props.allNotificationsRoute}/mark-all-as-read`)
            .then(() => {
                notifications.value.forEach(n => {
                    n.read_at = new Date().toISOString();
                });
                unreadCount.value = 0;
                count.value = 0;
                emit('count-updated', 0);
            })
            .catch(error => {
                console.error('Error marking all as read:', error);
            });
    };

    const getIcon = (type) => {
        const icons = {
            'invitation': '📧',
            'account_approved': '✅',
            'subscription_active': '💳',
            'subscription_expiring': '⚠️',
            'subscription_expired': '⛔',
            'subscription_grace_period': '⏳',
            'subscription_suspended': '🚫',
            'business_approved': '🎉',
            'business_published': '🚀',
            'business_rejected': '❌',
            'business_submitted': '📝',
            'new_review': '⭐',
            'admin_notification': '📢',
            'payment_received': '💰',
            'payment_failed': '❌',
            'payment_expired': '⏰',
            'subscription_renewal': '🔄',
            'subscription_request': '📋',
            'default': '📢',
        };
        return icons[type] || icons.default;
    };

    const formatTime = (date) => {
        const now = new Date();
        const diff = Math.floor((now - new Date(date)) / 1000);

        if (diff < 60) return 'Just now';
        if (diff < 3600) return Math.floor(diff / 60) + 'm';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h';
        if (diff < 172800) return 'Yesterday';
        if (diff < 604800) return Math.floor(diff / 86400) + 'd';
        return new Date(date).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric'
        });
    };

    const handleClickOutside = (event) => {
        if (isOpen.value && dropdownContainer.value) {
            if (!dropdownContainer.value.contains(event.target)) {
                closeDropdown();
            }
        }
    };

    const handleEscape = (event) => {
        if (event.key === 'Escape' && isOpen.value) {
            closeDropdown();
        }
    };

    const handleResize = () => {
        if (isOpen.value) {
            positionDropdown();
        }
    };

    let interval;

    onMounted(() => {
        document.addEventListener('click', handleClickOutside);
        document.addEventListener('keydown', handleEscape);
        window.addEventListener('resize', handleResize);
        window.addEventListener('scroll', handleResize);
        interval = setInterval(() => {
            if (!isOpen.value) {
                // ✅ Hit the dedicated count endpoint instead of the
                //    full dropdown endpoint — lighter payload.
                const countUrl = props.allNotificationsRoute + '/count';
                axios.get(countUrl)
                    .then(response => {
                        const newCount = response.data.unread_count || 0;
                        if (newCount !== unreadCount.value) {
                            unreadCount.value = newCount;
                            emit('count-updated', unreadCount.value);
                        }
                    })
                    .catch(error => {
                        console.error('Error refreshing notification count:', error);
                    });
            }
        }, 30000);
    });

    onUnmounted(() => {
        document.removeEventListener('click', handleClickOutside);
        document.removeEventListener('keydown', handleEscape);
        window.removeEventListener('resize', handleResize);
        window.removeEventListener('scroll', handleResize);
        if (interval) {
            clearInterval(interval);
        }
        document.body.style.overflow = '';
    });
</script>

<style scoped>
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .line-clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    @media (max-width: 640px) {
        .line-clamp-2 {
            -webkit-line-clamp: 3;
        }
    }
</style>