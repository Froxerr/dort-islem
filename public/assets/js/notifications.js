/**
 * Notification System for Friend Requests
 * Handles real-time notifications with Pusher
 */
class NotificationSystem {
    constructor() {
        this.pusher = null;
        this.userChannel = null;
        this.notifications = [];
        this.container = null;
        
        this.init();
    }
    
    init() {
        this.createContainer();
        this.initPusher();
        this.startProtectionMonitor(); // Global protection mechanism
    }
    
    /**
     * Notification container'ını oluştur
     */
    createContainer() {
        this.container = document.createElement('div');
        this.container.className = 'notification-container';
        this.container.id = 'notificationContainer';
        document.body.appendChild(this.container);
    }
    
    /**
     * Pusher'ı başlat ve user channel'ına subscribe ol
     */
    initPusher() {
        try {
            if (window.pusherKey && window.pusherCluster && typeof Pusher !== 'undefined') {
                const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
                
                if (!userId) {
                    return; // User not authenticated
                }
                
                this.pusher = new Pusher(window.pusherKey, {
                    cluster: window.pusherCluster,
                    encrypted: true,
                    authEndpoint: '/broadcasting/auth',
                    auth: {
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                            'Content-Type': 'application/x-www-form-urlencoded'
                        }
                    }
                });
                
                // Subscribe to user channel for notifications
                this.userChannel = this.pusher.subscribe(`private-user.${userId}`);
                
                // Listen for friend requests
                this.userChannel.bind('friend.request', (data) => {
                    this.showFriendRequestNotification(data);
                });
                
                this.userChannel.bind('pusher:subscription_succeeded', () => {
                    // Successfully subscribed to user channel
                });
                
                this.userChannel.bind('pusher:subscription_error', (error) => {
                    // Failed to subscribe to user channel
                });
                
            }
        } catch (error) {
            // Pusher initialization failed - silent fail
        }
    }
    
    /**
     * Global protection monitor - prevents notifications from disappearing
     */
    startProtectionMonitor() {
        setInterval(() => {
            // Check all notifications every 2 seconds
            this.notifications.forEach(notification => {
                if (notification.dataset.state === 'showing' && !this.container.contains(notification)) {
                    console.error('🚨 CRITICAL: Notification disappeared from DOM unexpectedly!', notification.id);
                    // Try to restore it
                    try {
                        this.container.appendChild(notification);
                        console.log('🔧 Attempted to restore disappeared notification:', notification.id);
                    } catch (error) {
                        console.error('❌ Failed to restore notification:', error);
                    }
                }
            });
        }, 2000);
    }
    
    /**
     * Arkadaşlık daveti bildirimi göster
     */
    showFriendRequestNotification(data) {
        console.log('🔔 Friend request notification triggered:', data);
        
        // Aynı ID'li notification zaten varsa gösterme (duplicate önleme)
        const existingNotification = document.getElementById(`friend-request-${data.id}`);
        if (existingNotification) {
            console.log('⚠️ Duplicate notification prevented:', data.id);
            return;
        }
        
        const notification = this.createNotificationElement({
            id: `friend-request-${data.id}`,
            type: 'friend-request',
            avatar: data.sender.profile_image,
            avatarFallback: data.sender.name ? data.sender.name.charAt(0).toUpperCase() : data.sender.username.charAt(0).toUpperCase(),
            title: `${data.sender.name || data.sender.username}`,
            text: 'size arkadaşlık daveti gönderdi',
            actions: [
                {
                    text: 'Kabul Et',
                    className: 'accept',
                    onClick: () => this.acceptFriendRequest(data.id, notification)
                },
                {
                    text: 'Reddet', 
                    className: 'reject',
                    onClick: () => this.rejectFriendRequest(data.id, notification)
                }
            ]
            // onClose removed - using force removal now
        });
        
        // Ultra-stable state management
        notification.dataset.state = 'showing';
        notification.dataset.createdAt = Date.now();
        notification.dataset.friendshipId = data.id;
        notification.dataset.protected = 'true'; // Protection flag
        
        // Force visibility
        notification.style.display = 'block';
        notification.style.visibility = 'visible';
        notification.style.opacity = '1';
        
        this.container.appendChild(notification);
        this.notifications.push(notification);
        
        console.log('✅ Notification added to DOM:', notification.id);
        
        // Extended auto remove timer (30 seconds for ultra stability)
        const autoRemoveTimer = setTimeout(() => {
            console.log('⏰ Auto-remove timer triggered for:', notification.id);
            if (this.container.contains(notification) && notification.dataset.state === 'showing') {
                this.forceRemoveNotification(notification, 'Auto-remove timer for friend request');
            }
        }, 30000);
        
        // Store timer reference for cleanup
        notification.autoRemoveTimer = autoRemoveTimer;
        
        // Additional stability check after 1 second
        setTimeout(() => {
            if (!this.container.contains(notification)) {
                console.error('🚨 Notification disappeared unexpectedly!', notification.id);
            } else {
                console.log('✅ Notification stability check passed:', notification.id);
            }
        }, 1000);
    }
    
    /**
     * Notification elementi oluştur
     */
    createNotificationElement(options) {
        const notification = document.createElement('div');
        notification.className = `notification ${options.type || ''}`;
        notification.id = options.id;
        
        // Avatar içeriği
        let avatarContent = options.avatarFallback;
        if (options.avatar) {
            avatarContent = `<img src="/storage/${options.avatar}" alt="${options.title}">`;
        }
        
        // Actions HTML
        let actionsHTML = '';
        if (options.actions && options.actions.length > 0) {
            const actionButtons = options.actions.map(action => 
                `<button class="notification-btn ${action.className}" data-action="${action.className}">${action.text}</button>`
            ).join('');
            
            actionsHTML = `<div class="notification-actions">${actionButtons}</div>`;
        }
        
        notification.innerHTML = `
            <div class="notification-header">
                <div class="notification-avatar">
                    ${avatarContent}
                </div>
                <div class="notification-info">
                    <div class="notification-title">${options.title}</div>
                    <div class="notification-text">${options.text}</div>
                </div>
                <button class="notification-close" data-action="close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            ${actionsHTML}
        `;
        
        // Event listeners
        notification.addEventListener('click', (e) => {
            const action = e.target.closest('[data-action]')?.getAttribute('data-action');
            
            if (action === 'close') {
                console.log('❌ Close button clicked for notification:', notification.id);
                // Force remove for user action
                this.forceRemoveNotification(notification, 'User clicked close button');
            } else if (action && options.actions) {
                const actionConfig = options.actions.find(a => a.className === action);
                if (actionConfig && actionConfig.onClick) {
                    actionConfig.onClick();
                }
            }
        });
        
        return notification;
    }
    
    /**
     * Arkadaşlık davetini kabul et
     */
    async acceptFriendRequest(friendshipId, notificationElement) {
        console.log('👍 Accept button clicked for friendship:', friendshipId);
        
        try {
            const response = await fetch('/profile/friends/accept-request', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    friendship_id: friendshipId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showSuccessNotification('Arkadaşlık daveti kabul edildi! 🎉');
                
                // Force remove - bypass protection for user action
                this.forceRemoveNotification(notificationElement, 'User accepted friend request');
                
                // Chat widget'ının friend listesini güncelle (eğer varsa)
                if (window.chatWidget && typeof window.chatWidget.loadFriends === 'function') {
                    window.chatWidget.loadFriends();
                }
            } else {
                this.showErrorNotification(data.message || 'Bir hata oluştu');
                // Even on error, remove the notification
                this.forceRemoveNotification(notificationElement, 'Accept request failed');
            }
        } catch (error) {
            this.showErrorNotification('Bağlantı hatası');
            // Even on error, remove the notification
            this.forceRemoveNotification(notificationElement, 'Accept request error');
        }
    }
    
    /**
     * Arkadaşlık davetini reddet
     */
    async rejectFriendRequest(friendshipId, notificationElement) {
        console.log('👎 Reject button clicked for friendship:', friendshipId);
        
        try {
            const response = await fetch('/profile/friends/reject-request', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    friendship_id: friendshipId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showInfoNotification('Arkadaşlık daveti reddedildi');
                
                // Force remove - bypass protection for user action
                this.forceRemoveNotification(notificationElement, 'User rejected friend request');
            } else {
                this.showErrorNotification(data.message || 'Bir hata oluştu');
                // Even on error, remove the notification
                this.forceRemoveNotification(notificationElement, 'Reject request failed');
            }
        } catch (error) {
            this.showErrorNotification('Bağlantı hatası');
            // Even on error, remove the notification
            this.forceRemoveNotification(notificationElement, 'Reject request error');
        }
    }
    
    /**
     * Başarı bildirimi göster
     */
    showSuccessNotification(message) {
        this.showSimpleNotification(message, 'success', '✅');
    }
    
    /**
     * Hata bildirimi göster
     */
    showErrorNotification(message) {
        this.showSimpleNotification(message, 'error', '❌');
    }
    
    /**
     * Bilgi bildirimi göster
     */
    showInfoNotification(message) {
        this.showSimpleNotification(message, 'info', 'ℹ️');
    }
    
    /**
     * Basit bildirim göster
     */
    showSimpleNotification(message, type, icon) {
        const notificationId = `simple-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
        
        const notification = this.createNotificationElement({
            id: notificationId,
            type: type,
            avatarFallback: icon,
            title: message,
            text: ''
            // onClose removed - using force removal now
        });
        
        // Stable state management
        notification.dataset.state = 'showing';
        
        this.container.appendChild(notification);
        this.notifications.push(notification);
        
        // Auto remove after 6 seconds (increased for stability)
        const autoRemoveTimer = setTimeout(() => {
            if (this.container.contains(notification) && notification.dataset.state === 'showing') {
                this.forceRemoveNotification(notification, 'Auto-remove timer for simple notification');
            }
        }, 6000);
        
        // Store timer reference for cleanup
        notification.autoRemoveTimer = autoRemoveTimer;
    }
    
    /**
     * Bildirimi kaldır - ultra defensive version
     */
    removeNotification(notification) {
        console.log('🗑️ Remove notification called for:', notification?.id);
        
        if (!notification || !this.container.contains(notification)) {
            console.log('⚠️ Notification not found or already removed:', notification?.id);
            return;
        }
        
        // Protection check
        if (notification.dataset.protected === 'true') {
            const createdAt = parseInt(notification.dataset.createdAt);
            const now = Date.now();
            const ageInSeconds = (now - createdAt) / 1000;
            
            // Don't remove if less than 5 seconds old (protection against premature removal)
            if (ageInSeconds < 5) {
                console.log('🛡️ Notification protected from premature removal:', notification.id, 'Age:', ageInSeconds, 'seconds');
                return;
            }
        }
        
        // Already being removed? Skip.
        if (notification.dataset.state === 'removing') {
            console.log('⚠️ Notification already being removed:', notification.id);
            return;
        }
        
        console.log('✅ Proceeding with notification removal:', notification.id);
        
        // Set state to removing
        notification.dataset.state = 'removing';
        
        // Clear auto remove timer if exists
        if (notification.autoRemoveTimer) {
            clearTimeout(notification.autoRemoveTimer);
            notification.autoRemoveTimer = null;
        }
        
        // Add removing class for animation
        notification.classList.add('removing');
        
        // Remove from DOM after animation completes
        setTimeout(() => {
            if (this.container.contains(notification)) {
                try {
                    this.container.removeChild(notification);
                    this.notifications = this.notifications.filter(n => n !== notification);
                    console.log('✅ Notification successfully removed from DOM:', notification.id);
                } catch (error) {
                    // Silent error handling
                    console.error('❌ Notification removal error:', error, notification.id);
                }
            } else {
                console.log('⚠️ Notification was already removed from DOM:', notification.id);
            }
        }, 400); // Increased wait time for stability
    }

    /**
     * Bildirimi zorla kaldır - ultra defensive version
     */
    forceRemoveNotification(notificationElement, reason) {
        console.log('🗑️ Force remove notification called for:', notificationElement?.id, 'Reason:', reason);

        if (!notificationElement || !this.container.contains(notificationElement)) {
            console.log('⚠️ Notification not found or already removed for force removal:', notificationElement?.id);
            return;
        }

        // Set state to removing
        notificationElement.dataset.state = 'removing';
        notificationElement.dataset.protected = 'false'; // Bypass protection

        // Clear auto remove timer if exists
        if (notificationElement.autoRemoveTimer) {
            clearTimeout(notificationElement.autoRemoveTimer);
            notificationElement.autoRemoveTimer = null;
        }

        // Add removing class for animation
        notificationElement.classList.add('removing');

        // Remove from DOM after animation completes
        setTimeout(() => {
            if (this.container.contains(notificationElement)) {
                try {
                    this.container.removeChild(notificationElement);
                    this.notifications = this.notifications.filter(n => n !== notificationElement);
                    console.log('✅ Notification successfully removed from DOM for force removal:', notificationElement.id);
                } catch (error) {
                    // Silent error handling
                    console.error('❌ Notification force removal error:', error, notificationElement.id);
                }
            } else {
                console.log('⚠️ Notification was already removed from DOM for force removal:', notificationElement.id);
            }
        }, 400); // Increased wait time for stability
    }
    
    /**
     * Tüm bildirimleri temizle
     */
    clearAllNotifications() {
        console.log('🗑️ Clearing all notifications');
        this.notifications.forEach(notification => {
            this.forceRemoveNotification(notification, 'Clear all notifications');
        });
    }
}

// Initialize notification system when DOM is ready
let notificationSystem;
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize for authenticated users
    if (document.querySelector('meta[name="user-id"]')) {
        notificationSystem = new NotificationSystem();
    }
});

// Global function for manual notifications
window.showNotification = function(message, type = 'info') {
    if (notificationSystem) {
        if (type === 'success') {
            notificationSystem.showSuccessNotification(message);
        } else if (type === 'error') {
            notificationSystem.showErrorNotification(message);
        } else {
            notificationSystem.showInfoNotification(message);
        }
    }
}; 