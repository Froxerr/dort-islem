class ChatWidget {
    constructor() {
        this.isOpen = false;
        this.currentConversation = null;
        this.currentFriend = null;
        this.friends = [];
        this.unreadCount = 0;
        this.messagePollingInterval = null;
        this.pusher = null;
        this.currentChannel = null;
        this.userChannel = null; // User-level channel for global message events
        this.isTyping = false;
        this.lastSoundPlay = null;
        this.lastTypingRequest = null; // Rate limiting için
        this.markAsReadTimeout = null; // Timeout for debounced read marking
        this.processedMessageIds = new Set(); // Track processed message IDs to prevent duplicates
        this.renderTimeout = null; // Debounced rendering timeout
        this.needsRender = false; // Flag to track if render is needed
        this.globalInterval = null; // Track global interval for cleanup
        this.unreadCheckTimeout = null; // Debounced unread check timeout
        this.lastAtBottomTime = null; // Track last time user was at bottom

        this.init();
    }

    init() {
        this.createWidget();
        this.initPusher();
        this.attachEventListeners();
        this.loadFriends();
        this.checkUnreadMessages();

        // Periyodik olarak okunmamış mesajları ve friend list'i kontrol et
        this.globalInterval = setInterval(() => {
            this.debouncedCheckUnreadMessages(); // OPTIMIZED: Debounced version
            // Friend list'i de düzenli olarak güncelle (fallback)
            this.refreshFriendsList();
            // ENHANCED: Proactive tick monitoring
            this.refreshMessageTicks();
            // ENHANCED: Periodic automatic read detection
            if (document.getElementById('chatWindow').classList.contains('active')) {
                this.checkAndMarkUnreadMessages();
            }
        }, 2000); // OPTIMIZED: 2 saniye - daha hızlı güncelleme
    }

    /**
     * Clean up resources
     */
    destroy() {
        // Clear all timeouts and intervals
        if (this.globalInterval) {
            clearInterval(this.globalInterval);
            this.globalInterval = null;
        }
        
        if (this.messagePollingInterval) {
            clearInterval(this.messagePollingInterval);
            this.messagePollingInterval = null;
        }
        
        if (this.markAsReadTimeout) {
            clearTimeout(this.markAsReadTimeout);
            this.markAsReadTimeout = null;
        }
        
        if (this.renderTimeout) {
            clearTimeout(this.renderTimeout);
            this.renderTimeout = null;
        }
        
        if (this.unreadCheckTimeout) {
            clearTimeout(this.unreadCheckTimeout);
            this.unreadCheckTimeout = null;
        }
        
        // Unsubscribe from Pusher channels
        this.unsubscribeFromPusherChannel();
        this.unsubscribeFromUserChannel();
        
        // Clear processed message IDs to free memory
        this.processedMessageIds.clear();
    }

    /**
     * Centralized error handling for consistency
     */
    handleError(error, context = '', silent = false) {
        const errorMessage = error?.message || error || 'Unknown error';
        
        if (!silent) {
            console.error(`ChatWidget ${context} error:`, errorMessage);
        }
        
        // Only show user-facing errors for critical operations
        const criticalContexts = ['sendMessage', 'openChat', 'loadFriends'];
        if (criticalContexts.includes(context)) {
            this.showError(this.getErrorMessage(context));
        }
    }

    /**
     * Get user-friendly error messages
     */
    getErrorMessage(context) {
        const messages = {
            'sendMessage': 'Mesaj gönderilemedi',
            'openChat': 'Sohbet açılamadı', 
            'loadFriends': 'Arkadaşlar yüklenemedi',
            'default': 'Bir hata oluştu, lütfen tekrar deneyin'
        };
        
        return messages[context] || messages.default;
    }

    /**
     * Pusher'ı başlat
     */
    initPusher() {
        try {
            if (window.pusherKey && window.pusherCluster && typeof Pusher !== 'undefined') {
                this.pusher = new Pusher(window.pusherKey, {
                    cluster: window.pusherCluster,
                    encrypted: true,
                    authEndpoint: '/broadcasting/auth',
                    auth: {
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                            'Content-Type': 'application/x-www-form-urlencoded'  // Pusher default format
                        }
                    }
                });

                // Connection event listeners - silent
                this.pusher.connection.bind('connected', () => {
                    // Connection established
                });

                this.pusher.connection.bind('disconnected', () => {
                    // Connection lost
                });

                this.pusher.connection.bind('error', (error) => {
                    // Connection error - fallback to polling
                    this.enablePollingMode();
                });

                // User-level channel subscribe (tüm mesajlar için)
                this.subscribeToUserChannel();
            } else {
                this.enablePollingMode();
            }
        } catch (error) {
            this.enablePollingMode();
        }
    }

    /**
     * Polling modunu etkinleştir (Pusher yoksa)
     */
    enablePollingMode() {
        this.pusher = null;
        // Fallback to polling mode
    }

    createWidget() {
        const widget = document.createElement('div');
        widget.className = 'chat-widget';
        widget.innerHTML = `
            <button class="chat-toggle" id="chatToggle">
                <i class="fas fa-comments"></i>
                <span class="unread-badge" id="unreadBadge" style="display: none;">0</span>
            </button>

            <div class="chat-container" id="chatContainer">
                <div class="chat-header">
                    <h3>Mesajlar</h3>
                    <button class="chat-close" id="chatClose">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="chat-body">
                    <!-- Friends List View -->
                    <div class="friends-list" id="friendsList">
                        <div class="loading">Arkadaşlar yükleniyor...</div>
                    </div>

                    <!-- Chat Window View -->
                    <div class="chat-window" id="chatWindow">
                        <div class="chat-window-header">
                            <button class="back-to-friends" id="backToFriends">
                                <i class="fas fa-arrow-left"></i>
                            </button>
                            <div class="current-friend-avatar" id="currentFriendAvatar"></div>
                            <div class="current-friend-name" id="currentFriendName"></div>
                        </div>

                        <div class="messages-container" id="messagesContainer">
                            <!-- Messages will be loaded here -->
                        </div>

                        <div class="message-input-container">
                            <form class="message-input-form" id="messageForm">
                                <textarea
                                    class="message-input"
                                    id="messageInput"
                                    placeholder="Mesajınızı yazın..."
                                    rows="1"
                                ></textarea>
                                <button type="submit" class="send-button" id="sendButton">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(widget);
    }

    attachEventListeners() {
        const toggleBtn = document.getElementById('chatToggle');
        const closeBtn = document.getElementById('chatClose');
        const backBtn = document.getElementById('backToFriends');
        const messageForm = document.getElementById('messageForm');
        const messageInput = document.getElementById('messageInput');

        toggleBtn.addEventListener('click', () => this.toggleChat());
        closeBtn.addEventListener('click', () => this.closeChat());
        backBtn.addEventListener('click', () => this.showFriendsList());
        messageForm.addEventListener('submit', (e) => this.sendMessage(e));

        // Auto-resize textarea
        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 80) + 'px';
        });

        // Typing indicator - SIMPLIFIED VERSION
        let typingTimer = null;
        let isProcessingTyping = false; // Processing flag

        const processTypingState = (shouldBeTyping) => {
            if (isProcessingTyping) return; // Prevent concurrent processing
            isProcessingTyping = true;

            try {
                // Sadece Pusher varsa typing indicator kullan
                if (!this.pusher || !this.currentConversation) {
                    isProcessingTyping = false;
                    return;
                }

                // FIXED: Single state tracking instead of duplicate
                if (shouldBeTyping === this.isTyping) {
                    isProcessingTyping = false;
                    return; // No change needed
                }

                // Update state
                this.isTyping = shouldBeTyping;

                // Send typing status
                this.sendTypingStatus(shouldBeTyping);

            } catch (error) {
                // Silent error handling for typing state processing  
            } finally {
                isProcessingTyping = false;
            }
        };

        messageInput.addEventListener('input', () => {
            const hasText = messageInput.value.trim() !== '';

            // Immediate typing start
            if (hasText && !this.isTyping) {
                processTypingState(true);
            }

            // Clear existing timer
            clearTimeout(typingTimer);

            if (hasText) {
                // Debounced typing stop (3 seconds after last keystroke)
                typingTimer = setTimeout(() => {
                    processTypingState(false);
                }, 3000);
            } else {
                // Immediate typing stop when empty
                processTypingState(false);
            }
        });

        // Enter to send, Shift+Enter for new line - stable version
        messageInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();

                // Clear all typing timers and states
                clearTimeout(typingTimer);
                processTypingState(false);

                this.sendMessage(e);
            }
        });

        // Focus lost - stable cleanup
        messageInput.addEventListener('blur', () => {
            clearTimeout(typingTimer);
            processTypingState(false);
        });

        // Message input event listeners
        const sendButton = document.getElementById('sendButton');

        // ENHANCED: Add scroll listener for automatic read detection
        const messagesContainer = document.getElementById('messagesContainer');
        if (messagesContainer) {
            messagesContainer.addEventListener('scroll', () => {
                // If user scrolls to bottom, mark messages as read
                const isAtBottom = messagesContainer.scrollTop + messagesContainer.clientHeight >= messagesContainer.scrollHeight - 10;
                if (isAtBottom) {
                    this.checkAndMarkUnreadMessages();
                }
                

            });
        }

        // ENHANCED: Add tab focus/blur listeners to handle tab switching
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                // Tab became inactive, reset bottom timer
                this.lastAtBottomTime = null;
            } else {
                // Tab became active, check if user is at bottom
                setTimeout(() => {
                    this.checkAndMarkUnreadMessages();
                }, 500); // Small delay to let UI settle
            }
        });

        // Typing detection - REMOVED DUPLICATE DECLARATION
        const doneTypingInterval = 1000;
    }

    toggleChat() {
        const container = document.getElementById('chatContainer');
        const toggleBtn = document.getElementById('chatToggle');

        this.isOpen = !this.isOpen;

        if (this.isOpen) {
            container.classList.add('active');
            toggleBtn.classList.add('active');
            this.showFriendsList();
        } else {
            container.classList.remove('active');
            toggleBtn.classList.remove('active');
            this.stopMessagePolling();
        }
    }

    closeChat() {
        this.isOpen = false;
        document.getElementById('chatContainer').classList.remove('active');
        document.getElementById('chatToggle').classList.remove('active');
        this.stopMessagePolling();
    }

    async loadFriends() {
        try {
            const response = await fetch('/messages/friends');
            if (response.ok) {
                this.friends = await response.json();
                // Arkadaşlar zaten backend'de sıralı geliyorlar ama emin olmak için
                this.sortFriendsByLastMessage();
                this.renderFriendsList();
            }
        } catch (error) {
            this.handleError(error, 'loadFriends');
        }
    }

    /**
     * Arkadaşları son mesaj zamanına göre sırala
     */
    sortFriendsByLastMessage() {
        this.friends.sort((a, b) => {
            const timeA = a.last_message_at ? new Date(a.last_message_at).getTime() : 0;
            const timeB = b.last_message_at ? new Date(b.last_message_at).getTime() : 0;
            return timeB - timeA; // Yeni mesaj atanlar üstte
        });
    }

    renderFriendsList() {
        const friendsList = document.getElementById('friendsList');

        if (this.friends.length === 0) {
            friendsList.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">🌟</div>
                    <p>Henüz arkadaşınız yok.<br>Arkadaş ekleyerek mesajlaşmaya başlayın! 🚀</p>
                </div>
            `;
            return;
        }

        friendsList.innerHTML = this.friends.map((friend, index) => {
            // Her arkadaş için farklı renk gradyanları
            const colors = [
                'linear-gradient(135deg, #74b9ff 0%, #0984e3 100%)',
                'linear-gradient(135deg, #fd79a8 0%, #e84393 100%)',
                'linear-gradient(135deg, #fdcb6e 0%, #e17055 100%)',
                'linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%)',
                'linear-gradient(135deg, #00b894 0%, #00cec9 100%)',
                'linear-gradient(135deg, #e17055 0%, #fab1a0 100%)'
            ];
            const avatarColor = colors[index % colors.length];

            const avatarContent = friend.profile_image
                ? `<img src="/storage/${friend.profile_image}" alt="${friend.name || friend.username}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`
                : `${friend.name ? friend.name.charAt(0).toUpperCase() : friend.username.charAt(0).toUpperCase()}`;

            const unreadCount = friend.unread_count || 0;
            const showUnread = unreadCount > 0;
            
            return `
                <div class="friend-item" data-friend-id="${friend.id}" onclick="chatWidget.openChat(${friend.id})">
                    <div class="friend-avatar" style="background: ${friend.profile_image ? 'transparent' : avatarColor}">
                        ${avatarContent}
                    </div>
                    <div class="friend-info">
                        <div class="friend-name">${friend.name || friend.username}</div>
                        <div class="friend-status">🟢 Aktif</div>
                    </div>
                    <div class="friend-unread" style="display: ${showUnread ? 'flex' : 'none'};">${unreadCount}</div>
                </div>
            `;
        }).join('');
    }

    async openChat(friendId) {
        const friend = this.friends.find(f => f.id === friendId);
        if (!friend) return;

        this.currentFriend = friend;

        try {
            // Sohbet oluştur veya mevcut sohbeti getir
            const response = await fetch('/messages/conversation', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ friend_id: friendId })
            });

            if (response.ok) {
                this.currentConversation = await response.json();
                // Ensure messages array is always initialized
                if (!this.currentConversation.messages) {
                    this.currentConversation.messages = [];
                }
                
                // Render messages
                this.renderMessages();
                
                // Show chat window
                this.showChatWindow();
                this.startMessagePolling();
            }
        } catch (error) {
            this.handleError(error, 'openChat');
        }
    }

    showChatWindow() {
        document.getElementById('friendsList').style.display = 'none';
        document.getElementById('chatWindow').classList.add('active');

        // Friend info'yu güncelle
        const avatar = document.getElementById('currentFriendAvatar');
        const name = document.getElementById('currentFriendName');

        // Arkadaş avatarını güncelle
        if (this.currentFriend.profile_image) {
            avatar.style.background = 'transparent';
            avatar.innerHTML = `<img src="/storage/${this.currentFriend.profile_image}" alt="${this.currentFriend.name || this.currentFriend.username}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
        } else {
            // Arkadaş için renk seç
            const friendIndex = this.friends.findIndex(f => f.id === this.currentFriend.id);
            const colors = [
                'linear-gradient(135deg, #74b9ff 0%, #0984e3 100%)',
                'linear-gradient(135deg, #fd79a8 0%, #e84393 100%)',
                'linear-gradient(135deg, #fdcb6e 0%, #e17055 100%)',
                'linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%)',
                'linear-gradient(135deg, #00b894 0%, #00cec9 100%)',
                'linear-gradient(135deg, #e17055 0%, #fab1a0 100%)'
            ];
            const avatarColor = colors[friendIndex % colors.length];

                    avatar.style.background = avatarColor;
        avatar.textContent = this.currentFriend.name ?
            this.currentFriend.name.charAt(0).toUpperCase() :
            this.currentFriend.username.charAt(0).toUpperCase();
        }
        name.textContent = this.currentFriend.name || this.currentFriend.username;

        // Pusher channel'ına subscribe ol
        this.subscribeToPusherChannel();

        // Mark messages as read when chat window is opened (after a brief delay)
        setTimeout(() => {
            this.markMessagesAsRead();
        }, 500); // Brief delay to ensure messages are rendered

        // ENHANCED: Immediate read detection when chat opens
        setTimeout(() => {
            this.checkAndMarkUnreadMessages();
        }, 200); // Faster than markMessagesAsRead for immediate visual feedback
    }

    showFriendsList() {
        document.getElementById('friendsList').style.display = 'block';
        document.getElementById('chatWindow').classList.remove('active');
        
        // Clear any pending mark as read timeouts
        if (this.markAsReadTimeout) {
            clearTimeout(this.markAsReadTimeout);
            this.markAsReadTimeout = null;
        }
        
        // Clear any pending render timeouts
        if (this.renderTimeout) {
            clearTimeout(this.renderTimeout);
            this.renderTimeout = null;
            this.needsRender = false;
        }
        
        this.currentConversation = null;
        this.currentFriend = null;
        this.stopMessagePolling();
        this.unsubscribeFromPusherChannel();
        // User channel'a tekrar subscribe ol (friend list güncellemeleri için)
        if (this.pusher && !this.userChannel) {
            this.subscribeToUserChannel();
        }
    }







    renderMessages() {
        const container = document.getElementById('messagesContainer');

        if (!this.currentConversation || !this.currentConversation.messages) {
            container.innerHTML = `
                <div class="empty-state">
                    <p>Henüz mesaj yok.<br>İlk mesajı siz gönderin!</p>
                </div>
            `;
            return;
        }

        container.innerHTML = this.currentConversation.messages.map(message => {
            const isOwn = message.user_id == document.querySelector('meta[name="user-id"]')?.getAttribute('content');
            const time = new Date(message.created_at).toLocaleTimeString('tr-TR', {
                hour: '2-digit',
                minute: '2-digit'
            });

            // Mesaj durumunu belirle (okundu/okunmadı)
            let tickStatus = 'sent';
            if (message.read_at) {
                tickStatus = 'read';
            } else if (message.created_at) {
                tickStatus = 'delivered';
            }

            // Avatar içeriği
            let messageAvatarContent = '';
            if (!isOwn && message.user) {
                const senderAvatarContent = message.user.profile_image
                    ? `<img src="/storage/${message.user.profile_image}" alt="${message.user.name || message.user.username}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`
                    : `${message.user.name ? message.user.name.charAt(0).toUpperCase() : message.user.username.charAt(0).toUpperCase()}`;

                const senderIndex = this.friends.findIndex(f => f.id === message.user.id);
                const colors = [
                    'linear-gradient(135deg, #74b9ff 0%, #0984e3 100%)',
                    'linear-gradient(135deg, #fd79a8 0%, #e84393 100%)',
                    'linear-gradient(135deg, #fdcb6e 0%, #e17055 100%)',
                    'linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%)',
                    'linear-gradient(135deg, #00b894 0%, #00cec9 100%)',
                    'linear-gradient(135deg, #e17055 0%, #fab1a0 100%)'
                ];
                const senderAvatarColor = colors[senderIndex % colors.length];

                messageAvatarContent = `
                    <div class="message-avatar" style="background: ${message.user.profile_image ? 'transparent' : senderAvatarColor}">
                        ${senderAvatarContent}
                    </div>
                `;
            }

            return `
                <div class="message ${isOwn ? 'own' : 'other'}" data-message-id="${message.id}">
                    ${messageAvatarContent}
                    <div class="message-bubble">
                        <div class="message-content">
                            ${this.escapeHtml(message.body)}
                        </div>
                        <div class="message-footer">
                            <span class="message-time">${time}</span>
                            ${isOwn ? `<span class="message-tick ${tickStatus}"></span>` : ''}
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        // Scroll to bottom with smooth animation
        container.scrollTo({
            top: container.scrollHeight,
            behavior: 'smooth'
        });

        // ENHANCED: Check for unread messages immediately after render
        setTimeout(() => {
            this.checkAndMarkUnreadMessages();
        }, 100);

        // Only mark messages as read when user is actively viewing the chat
        // NOT on every render - this was causing auto-read after 2 seconds
        this.scheduleMarkAsRead();
        
        // ENHANCED: Proactive tick monitoring after render
        setTimeout(() => {
            this.refreshMessageTicks();
        }, 100); // Small delay to let DOM settle
    }

    /**
     * Schedule marking messages as read with debouncing - ENHANCED VERSION
     */
    scheduleMarkAsRead() {
        // Clear existing timeout
        if (this.markAsReadTimeout) {
            clearTimeout(this.markAsReadTimeout);
        }

        // Only mark as read if chat window is active and visible
        if (document.getElementById('chatWindow').classList.contains('active')) {
            // ENHANCED: Immediate check for unread messages
            this.checkAndMarkUnreadMessages();
            
            // Debounce marking as read to avoid too frequent API calls
            this.markAsReadTimeout = setTimeout(() => {
                this.markMessagesAsRead();
            }, 1000); // 1 second delay instead of immediate
        }
    }

    /**
     * Check for unread messages and mark them as read only when user is actively viewing
     */
    checkAndMarkUnreadMessages() {
        if (!this.currentConversation || !this.currentConversation.messages) {
            return;
        }

        // Only mark as read if tab is active and user is at the bottom
        if (document.hidden) {
            return; // Tab is not active, don't mark as read
        }

        // Only mark as read if user is at the bottom of the chat (actively viewing)
        const messagesContainer = document.getElementById('messagesContainer');
        if (!messagesContainer) return;

        const isAtBottom = messagesContainer.scrollTop + messagesContainer.clientHeight >= messagesContainer.scrollHeight - 10;
        
        // Additional check: Only mark as read if user has been at bottom for at least 1 second
        if (!isAtBottom) {
            // Reset the bottom timer if user is not at bottom
            this.lastAtBottomTime = null;
            return; // User is not at bottom, don't mark as read
        }

        // Check if user has been at bottom for at least 1 second
        const now = Date.now();
        if (!this.lastAtBottomTime) {
            this.lastAtBottomTime = now;
            return; // Just reached bottom, wait 1 second
        }

        if (now - this.lastAtBottomTime < 1000) {
            return; // Not enough time at bottom yet
        }

        const currentUserId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
        const unreadMessages = this.currentConversation.messages.filter(message =>
            message.user_id != currentUserId && !message.read_at
        );

        if (unreadMessages.length > 0) {
            // Only mark as read when user has been at bottom for at least 1 second AND tab is active
            const readTime = new Date().toISOString();
            unreadMessages.forEach(message => {
                message.read_at = readTime;
                // Update tick visually for immediate feedback
                this.updateMessageTick(message.id, 'read');
            });

            // Send to server in background
            this.sendMarkAsReadToServer(unreadMessages.map(m => m.id));
        }
    }

    /**
     * Send mark as read to server in background (non-blocking)
     */
    async sendMarkAsReadToServer(messageIds) {
        try {
            const response = await fetch('/messages/mark-read-batch', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ message_ids: messageIds })
            });

            if (response.ok) {
                // Update friend list unread count
                if (this.currentFriend) {
                    const friend = this.friends.find(f => f.id == this.currentFriend.id);
                    if (friend) {
                        friend.unread_count = 0;
                        this.renderFriendsList();
                    }
                }
            } else {
                // Silent error handling - visual update already done
            }
        } catch (error) {
            // Silent error handling - visual update already done
        }
    }

    async sendMessage(e) {
        e.preventDefault();

        const input = document.getElementById('messageInput');
        const message = input.value.trim();

        if (!message || !this.currentConversation) return;

        // Optimize: Clear input immediately for better UX
        input.value = '';
        input.style.height = 'auto';

        // Temporarily pause polling during message send to prevent conflicts
        const wasPolling = !!this.messagePollingInterval;
        if (wasPolling) {
            this.stopMessagePolling();
        }

        // Non-blocking typing cleanup - don't wait for this
        this.sendTypingStatus(false).catch(() => {});
        this.isTyping = false;

        // Show optimistic message immediately
        const tempMessage = {
            id: 'temp-' + Date.now(),
            conversation_id: this.currentConversation.id,
            user_id: parseInt(document.querySelector('meta[name="user-id"]')?.getAttribute('content')),
            body: message,
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString(),
            read_at: null,
            user: {
                id: parseInt(document.querySelector('meta[name="user-id"]')?.getAttribute('content')),
                name: 'Sen', // Temporary display name
                username: 'you',
                profile_image: null
            },
            temp: true // Mark as temporary
        };

        // Add temp message to UI immediately
        this.currentConversation.messages.push(tempMessage);
        
        this.immediateRenderMessages(); // Immediate render for user action

        try {
            const response = await fetch('/messages/send', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    conversation_id: this.currentConversation.id,
                    body: message
                })
            });

            if (response.ok) {
                const data = await response.json();
                const newMessage = data.message || data;
                
                // Add our own message to processed IDs to prevent duplicate handling
                if (this.processedMessageIds && newMessage.id) {
                    this.processedMessageIds.add(newMessage.id);
                    this.processedMessageIds.add(`${newMessage.id}_${newMessage.conversation_id}`);
                }
                
                // Replace temp message with real message
                const tempIndex = this.currentConversation.messages.findIndex(m => m.temp);
                if (tempIndex !== -1) {
                    const tempMessage = this.currentConversation.messages[tempIndex];
                    
                    // OPTIMIZED: Check if content is identical before replacing
                    const contentChanged = tempMessage.body !== newMessage.body;
                    
                    // Update message ID in DOM
                    this.updateMessageIdInDOM(tempMessage.id, newMessage.id);
                    
                    // Replace temp with real message
                    this.currentConversation.messages[tempIndex] = newMessage;
                    

                    
                    // Only render if content actually changed
                    if (contentChanged) {
                        this.debouncedRenderMessages(); // Content changed, render needed
                    }
                    // Note: 99% of the time, temp message body === real message body
                    // so no render needed! This eliminates the unnecessary render.
                } else {
                    this.currentConversation.messages.push(newMessage);
                    
                    this.debouncedRenderMessages(); // New message, render needed
                }
                
                // OPTIMIZED: Immediate unread count update - no delay
                this.smartCheckUnreadMessages(); // OPTIMIZED: Smart update based on chat state
            } else {
                // Remove temp message on error
                this.currentConversation.messages = this.currentConversation.messages.filter(m => !m.temp);
                this.immediateRenderMessages(); // Immediate render for error state
                this.handleError(null, 'sendMessage');
            }
        } catch (error) {
            // Remove temp message on error
            this.currentConversation.messages = this.currentConversation.messages.filter(m => !m.temp);
            this.immediateRenderMessages(); // Immediate render for error state
            this.handleError(error, 'sendMessage');
        } finally {
            // Restart polling if it was paused
            if (wasPolling) {
                this.startMessagePolling();
            }
        }
    }

    startMessagePolling() {
        // FIXED: Only start polling if no real-time connection
        if (this.pusher) {
            return; // Don't poll if we have real-time connection
        }

        if (this.messagePollingInterval) {
            clearInterval(this.messagePollingInterval);
        }

        this.messagePollingInterval = setInterval(async () => {
            if (this.currentConversation) {
                await this.refreshMessages();
            }
        }, 2000); // Slower polling since it's only fallback
    }

    stopMessagePolling() {
        if (this.messagePollingInterval) {
            clearInterval(this.messagePollingInterval);
            this.messagePollingInterval = null;
        }
    }

    async refreshMessages() {
        // Early return if no conversation
        if (!this.currentConversation || !this.currentConversation.id) {
            return;
        }
        
        // Store conversation reference to check consistency throughout the function
        const conversationRef = this.currentConversation;
        
        try {
            const response = await fetch(`/messages/conversation/${conversationRef.id}`);
            
            // Double-check conversation is still active after async operation
            if (!this.currentConversation || this.currentConversation.id !== conversationRef.id) {
                return; // User navigated away during fetch
            }
            
            if (response.ok) {
                const messages = await response.json();

                // Ensure conversation messages array is initialized
                if (!this.currentConversation.messages) {
                    this.currentConversation.messages = [];
                }

                // Enhanced message comparison and merging logic
                const oldLength = this.currentConversation.messages.length;
                
                // Check if we need to update messages
                let needsUpdate = false;
                
                if (messages.length !== oldLength) {
                    needsUpdate = true; // New messages added
                } else {
                    // Check if any existing messages have changed (read status, etc.)
                    for (let i = 0; i < messages.length; i++) {
                        const newMsg = messages[i];
                        const oldMsg = this.currentConversation.messages[i];
                        
                        if (!oldMsg || oldMsg.id !== newMsg.id || 
                            oldMsg.read_at !== newMsg.read_at) {
                            needsUpdate = true;
                            break;
                        }
                    }
                }
                
                if (needsUpdate) {
                    // Merge logic to prevent duplicates
                    const newMessages = [];
                    
                    for (const message of messages) {
                        // Skip if this message is already being processed from another source
                        const messageKey = `${message.id}_${message.conversation_id}`;
                        if (this.processedMessageIds && 
                            (this.processedMessageIds.has(message.id) || this.processedMessageIds.has(messageKey))) {
                            // Message already processed from real-time or send, but still add to array for consistency
                            const existingMessage = this.currentConversation.messages.find(m => m.id == message.id);
                            if (existingMessage) {
                                // Update read status if changed
                                if (existingMessage.read_at !== message.read_at) {
                                    existingMessage.read_at = message.read_at;
                                }
                                newMessages.push(existingMessage);
                            } else {
                                newMessages.push(message);
                            }
                        } else {
                            // New message, add to processed IDs
                            if (this.processedMessageIds) {
                                this.processedMessageIds.add(message.id);
                                this.processedMessageIds.add(messageKey);
                            }
                            newMessages.push(message);
                        }
                    }
                    
                    this.currentConversation.messages = newMessages;
                    this.debouncedRenderMessages(); // Debounced render for polling updates
                    
                    // OPTIMIZED: Immediate unread count update instead of 100ms delay
                    this.debouncedCheckUnreadMessages(); // OPTIMIZED: Debounced to prevent multiple calls
                    
                    // Friend list'i de güncelle (real-time için)
                    this.loadFriends();
                }
            }
        } catch (error) {
            console.error('RefreshMessages error:', error);
            // Conversation geçersizse polling'i durdur
            if (error.message && error.message.includes('404')) {
                this.stopMessagePolling();
            }
        }
    }

    async checkUnreadMessages() {
        try {
            const response = await fetch('/messages/unread-total');
            if (response.ok) {
                const data = await response.json();
                this.updateUnreadBadge(data.count);
            }
        } catch (error) {
            // Silent error handling for background unread check
        }
    }

    /**
     * Debounced unread count check to prevent multiple simultaneous requests
     */
    debouncedCheckUnreadMessages() {
        // Clear existing timeout
        if (this.unreadCheckTimeout) {
            clearTimeout(this.unreadCheckTimeout);
        }
        
        // Schedule check with minimal delay to batch multiple updates
        this.unreadCheckTimeout = setTimeout(() => {
            this.checkUnreadMessages().catch(() => {});
            this.unreadCheckTimeout = null;
        }, 100); // 100ms debounce to batch rapid calls
    }

    /**
     * Smart unread count check - immediate if chat is open, debounced if closed
     */
    smartCheckUnreadMessages() {
        if (this.isOpen) {
            // Chat is open, update immediately for better UX
            this.checkUnreadMessages().catch(() => {});
        } else {
            // Chat is closed, use debounced version to batch updates
            this.debouncedCheckUnreadMessages();
        }
    }

    /**
     * Friend list'i yeniden yükle (polling fallback için)
     */
    async refreshFriendsList() {
        try {
            const response = await fetch('/messages/friends');
            if (response.ok) {
                const newFriends = await response.json();
                
                // Mevcut friends array ile karşılaştır
                const hasChanges = this.detectFriendListChanges(newFriends);
                
                if (hasChanges) {
                    this.friends = newFriends;
                    this.sortFriendsByLastMessage();
                    this.renderFriendsList();
                }
            }
        } catch (error) {
            // Silent error handling for periodic background updates
        }
    }

    /**
     * Friend list'te değişiklik olup olmadığını kontrol et
     */
    detectFriendListChanges(newFriends) {
        if (!this.friends || this.friends.length !== newFriends.length) {
            return true;
        }

        for (let i = 0; i < newFriends.length; i++) {
            const newFriend = newFriends[i];
            const oldFriend = this.friends[i];

            if (!oldFriend || 
                newFriend.id !== oldFriend.id ||
                (newFriend.unread_count || 0) !== (oldFriend.unread_count || 0) ||
                newFriend.last_message_at !== oldFriend.last_message_at) {
                return true;
            }
        }

        return false;
    }

    updateUnreadBadge(count) {
        // Aynı count değeriyse güncelleme yapma (performance optimization)
        if (this.unreadCount === count) {
            return;
        }
        
        const badge = document.getElementById('unreadBadge');
        this.unreadCount = count;

        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'flex';
            
            // Yeni mesaj geldiğinde subtle animation ekle
            badge.style.transform = 'scale(1.2)';
            setTimeout(() => {
                badge.style.transform = 'scale(1)';
            }, 200);
        } else {
            badge.style.display = 'none';
        }
    }

    showError(message) {
        // Burada toast notification gösterebilirsiniz
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Sohbetteki okunmamış mesajları okundu olarak işaretle - OPTIMIZED VERSION
     */
    async markMessagesAsRead() {
        if (!this.currentConversation || !this.currentConversation.messages) return;

        const currentUserId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
        const unreadMessages = this.currentConversation.messages.filter(message =>
            message.user_id != currentUserId && !message.read_at
        );

        if (unreadMessages.length === 0) return;

        // Arkadaş listesindeki unread count'u sıfırla
        if (this.currentFriend) {
            const friend = this.friends.find(f => f.id == this.currentFriend.id);
            if (friend) {
                friend.unread_count = 0;
                this.renderFriendsList();
            }
        }

        // FIXED: Batch all message IDs into single request instead of N+1
        const messageIds = unreadMessages.map(m => m.id);
        
        try {
            const response = await fetch('/messages/mark-read-batch', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ message_ids: messageIds })
            });

            if (response.ok) {
                // Mark all messages as read locally
                const now = new Date().toISOString();
                unreadMessages.forEach(message => {
                    message.read_at = now;
                    // ENHANCED: Update tick visually for immediate feedback
                    this.updateMessageTick(message.id, 'read');
                });
            }
        } catch (error) {
            // Fallback to individual requests if batch endpoint doesn't exist
            for (const message of unreadMessages) {
                try {
                    const response = await fetch('/messages/mark-read', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ message_id: message.id })
                    });

                    if (response.ok) {
                        message.read_at = new Date().toISOString();
                        // ENHANCED: Update tick visually for immediate feedback
                        this.updateMessageTick(message.id, 'read');
                    }
                } catch (innerError) {
                    // Silent error handling for individual mark as read
                }
            }
        }
    }

    /**
     * Pusher channel'ına subscribe ol
     */
    subscribeToPusherChannel() {
        if (!this.pusher || !this.currentConversation) {
            // Pusher yoksa polling başlat
            this.startMessagePolling();
            return;
        }

        try {
            // Önceki channel'dan unsubscribe ol
            this.unsubscribeFromPusherChannel();

            // Direkt private channel'ı dene
            const channelName = `private-conversation.${this.currentConversation.id}`;
            this.currentChannel = this.pusher.subscribe(channelName);

            this.currentChannel.bind('pusher:subscription_succeeded', () => {
                // Successfully subscribed to real-time channel
            });

            this.currentChannel.bind('pusher:subscription_error', (error) => {
                // Failed to subscribe - try again in 5 seconds
                setTimeout(() => {
                    this.subscribeToPusherChannel();
                }, 5000);
            });

            // Yeni mesaj geldiğinde
            this.currentChannel.bind('message.sent', (data) => {
                this.handleNewMessage(data.message);
            });

            // Typing indicator
            this.currentChannel.bind('user.typing', (data) => {
                this.handleTypingIndicator(data);
            });

            // Message read status
            this.currentChannel.bind('message.read', (data) => {
                this.handleMessageRead(data);
            });


        } catch (error) {
            // Failed to subscribe - try again in 5 seconds
            setTimeout(() => {
                this.subscribeToPusherChannel();
            }, 5000);
        }
    }



    /**
     * User-level channel'a subscribe ol (tüm mesajlar için)
     */
    subscribeToUserChannel() {
        if (!this.pusher) return;

        try {
            const currentUserId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
            if (!currentUserId) return;

            const channelName = `private-user.${currentUserId}`;
            this.userChannel = this.pusher.subscribe(channelName);

            this.userChannel.bind('pusher:subscription_succeeded', () => {
                // Successfully subscribed to user channel
            });

            this.userChannel.bind('pusher:subscription_error', (error) => {
                // Failed to subscribe to user channel - silent handling
            });

            // Global mesaj event'i (friend list güncellemesi için)
            this.userChannel.bind('message.received', (data) => {
                this.handleGlobalMessage(data.message);
            });

        } catch (error) {
            // Silent error handling for user channel subscription
        }
    }

    /**
     * Global mesaj handler (friend list güncellemesi için)
     */
    handleGlobalMessage(message) {
        // Friend list'i her zaman güncelle
        this.updateFriendsList(message);
        
        // OPTIMIZED: Debounced unread count update to prevent multiple calls
        this.smartCheckUnreadMessages();
    }

    /**
     * Pusher channel'dan unsubscribe ol
     */
    unsubscribeFromPusherChannel() {
        if (this.currentChannel) {
            this.pusher.unsubscribe(this.currentChannel.name);
            this.currentChannel = null;
        }
    }

    /**
     * User channel'dan unsubscribe ol
     */
    unsubscribeFromUserChannel() {
        if (this.userChannel) {
            this.pusher.unsubscribe(this.userChannel.name);
            this.userChannel = null;
        }
    }

    /**
     * Yeni mesajı handle et - OPTIMIZED VERSION
     */
    handleNewMessage(message) {
        // Early return for own messages to prevent duplicates
        const currentUserId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
        if (message.user_id == currentUserId) {
            return; // Skip own messages from real-time events
        }

        // Add to conversation messages
        if (this.currentConversation) {
            this.currentConversation.messages.push(message);
            
            // Only render if chat window is active
            if (document.getElementById('chatWindow').classList.contains('active')) {
                this.debouncedRenderMessages();
            }
        }

        // Update friend list
        this.updateFriendsList(message);
    }

    /**
     * Arkadaş listesini güncelle (yeni mesaj geldiğinde)
     */
    updateFriendsList(message) {
        const senderId = parseInt(message.user_id);
        const friend = this.friends.find(f => parseInt(f.id) === senderId);
        
        if (friend) {
            // Sadece kendi mesajımız değilse unread count'u artır
            const currentUserId = parseInt(document.querySelector('meta[name="user-id"]')?.getAttribute('content'));
            
            if (senderId !== currentUserId) {
                // Unread count'u artır
                friend.unread_count = (friend.unread_count || 0) + 1;
            }
            
            // Son mesaj zamanını güncelle
            friend.last_message_at = message.created_at || new Date().toISOString();
            
            // Listeyi yeniden sırala
            this.sortFriendsByLastMessage();
            // UI'ı güncelle
            this.renderFriendsList();
        }
    }

    /**
     * Mesaj okundu durumu güncellendiğinde çalışır - OPTIMIZED VERSION
     */
    handleMessageRead(data) {
        if (!this.currentConversation || data.conversation_id != this.currentConversation.id) {
            return;
        }

        // İlgili mesajı bul ve read_at'ini güncelle
        const message = this.currentConversation.messages.find(m => m.id == data.message_id);
        if (message) {
            // Sadece read_at'i güncelle
            message.read_at = data.read_at;

            // ENHANCED: Eğer mesaj kendi mesajımızsa ve DOM'da yoksa, mini render yap
            const currentUserId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
            const isOwnMessage = message.user_id == currentUserId;
            
            if (isOwnMessage) {
                // Check if our own message exists in DOM
                const messageContainer = document.querySelector(`[data-message-id="${message.id}"]`);
                if (!messageContainer) {
                    // Message not in DOM, just update data - no flicker
                    console.log('📝 [MESSAGE READ] Message not in DOM, just updating data');
                } else {
                    // Message exists in DOM, update tick without flicker
                    console.log('✅ [MESSAGE READ] Message in DOM, updating tick smoothly');
                    this.updateTickOnly(message.id, 'read');
                }
            }
        }
    }

    /**
     * Update message ID in DOM when temp message is replaced with real message
     */
    updateMessageIdInDOM(oldId, newId) {
        const messageElement = document.querySelector(`[data-message-id="${oldId}"]`);
        if (messageElement) {
            messageElement.setAttribute('data-message-id', newId);
            console.log(`🔄 [MESSAGE ID] Updated DOM message ID from ${oldId} to ${newId}`);
        }
    }

    /**
     * Update only the tick element without DOM flicker
     */
    updateTickOnly(messageId, tickStatus) {
        const messageContainer = document.querySelector(`[data-message-id="${messageId}"]`);
        if (!messageContainer) return;

        // Find or create tick element
        let tickElement = messageContainer.querySelector('.message-tick');
        if (!tickElement) {
            // Create tick element if it doesn't exist
            const messageFooter = messageContainer.querySelector('.message-footer');
            if (messageFooter) {
                tickElement = document.createElement('span');
                tickElement.className = 'message-tick';
                messageFooter.appendChild(tickElement);
            }
        }

        if (tickElement) {
            // Smooth transition for tick update
            tickElement.style.transition = 'all 0.3s ease';
            
            // Update classes
            tickElement.classList.remove('sent', 'delivered', 'read');
            tickElement.classList.add(tickStatus);
            
            // Visual feedback without flicker
            if (tickStatus === 'read') {
                tickElement.style.transform = 'scale(1.1)';
                setTimeout(() => {
                    tickElement.style.transform = 'scale(1)';
                }, 150);
            }
        }
    }

    /**
     * Manually add tick element to existing message without full render
     */
    addTickToExistingMessage(messageId) {
        const messageContainer = document.querySelector(`[data-message-id="${messageId}"]`);
        if (!messageContainer) return null;

        const messageFooter = messageContainer.querySelector('.message-footer');
        if (!messageFooter) return null;

        // Check if tick already exists
        let tickElement = messageFooter.querySelector('.message-tick');
        if (!tickElement) {
            // Create tick element
            tickElement = document.createElement('span');
            tickElement.className = 'message-tick';
            messageFooter.appendChild(tickElement);
        }

        return tickElement;
    }

    /**
     * Manually create tick element for a message without full render
     */
    createTickElement(messageId) {
        const messageContainer = document.querySelector(`[data-message-id="${messageId}"]`);
        if (messageContainer) {
            const messageFooter = messageContainer.querySelector('.message-footer');
            if (messageFooter && !messageFooter.querySelector('.message-tick')) {
                // Create tick element
                const tickElement = document.createElement('span');
                tickElement.className = 'message-tick read';
                messageFooter.appendChild(tickElement);
                return tickElement;
            }
        }
        return null;
    }

    /**
     * Tek bir mesajın tick durumunu güncelle (performance optimization) - ENHANCED VERSION
     */
    updateMessageTick(messageId, tickStatus) {
        const messageElement = document.querySelector(`[data-message-id="${messageId}"] .message-tick`);
        if (messageElement) {
            // Mevcut class'ları temizle
            messageElement.classList.remove('sent', 'delivered', 'read');
            // Yeni status'u ekle
            messageElement.classList.add(tickStatus);
            
            // Visual feedback için subtle animation
            if (tickStatus === 'read') {
                messageElement.style.transform = 'scale(1.1)';
                setTimeout(() => {
                    messageElement.style.transform = 'scale(1)';
                }, 150);
            }
            
            return messageElement;
        } else {
            // Element bulunamadıysa, mesaj data'sını direkt güncelle ve render'a güven
            const message = this.currentConversation?.messages?.find(m => m.id == messageId);
            if (message && tickStatus === 'read') {
                message.read_at = message.read_at || new Date().toISOString();
                // Don't trigger full render, just update the data
            }
            
            return null;
        }
    }

    /**
     * Single message tick mini-render - fallback method
     */
    updateSingleMessageTick(messageId, message) {
        const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
        if (messageElement) {
            const tickElement = messageElement.querySelector('.message-tick');
            if (tickElement) {
                // Determine correct tick status
                let tickStatus = 'sent';
                if (message.read_at) {
                    tickStatus = 'read';
                } else if (message.created_at) {
                    tickStatus = 'delivered';
                }
                
                // Update classes
                tickElement.classList.remove('sent', 'delivered', 'read');
                tickElement.classList.add(tickStatus);
                
                // Visual feedback
                if (tickStatus === 'read') {
                    tickElement.style.color = '#00d4aa';
                    tickElement.style.transform = 'scale(1.1)';
                    setTimeout(() => {
                        tickElement.style.transform = 'scale(1)';
                    }, 150);
                }
            }
        }
    }

    /**
     * Typing indicator'ı handle et
     */
    handleTypingIndicator(data) {
        const currentUserId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');

        // Kendi typing'ımızı gösterme
        if (data.user.id == currentUserId) return;

        let typingIndicator = document.getElementById('typingIndicator');

        if (data.is_typing) {
            if (!typingIndicator) {
                const indicator = document.createElement('div');
                indicator.id = 'typingIndicator';
                indicator.className = 'typing-indicator';
                indicator.innerHTML = `
                    <div class="typing-text">
                        <span>${data.user.name || data.user.username} yazıyor</span>
                        <div class="typing-dots">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                `;
                document.getElementById('messagesContainer').appendChild(indicator);

                // Scroll to bottom
                const container = document.getElementById('messagesContainer');
                container.scrollTo({
                    top: container.scrollHeight,
                    behavior: 'smooth'
                });
            }
        } else {
            // is_typing false geldiğinde direkt kaldır
            if (typingIndicator) {
                typingIndicator.remove();
            }
        }
    }

    /**
     * Mesaj ses efekti
     */
    playMessageSound() {
        // Ses spam'ini önlemek için rate limiting
        const now = Date.now();
        if (this.lastSoundPlay && (now - this.lastSoundPlay) < 1000) {
            return; // 1 saniye içinde tekrar ses çalma
        }
        this.lastSoundPlay = now;

        // Basit bir beep sesi
        try {
            const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmQcBzqF0fS+cSADKIXS8eSNOwcZaLvl6JdKDAhWq+Pwl0wRCkGc3O3UfSQEB3fH8cCb');
            audio.volume = 0.3;
            audio.play().catch(() => {
                // Ses çalınmazsa sessizce devam et
            });
        } catch (error) {
            // Ses hatası varsa logla ama durduramaz
        }
    }



    /**
     * Typing durumunu gönder - stable version with error handling
     */
    async sendTypingStatus(isTyping) {
        // Rate limiting: Maximum 1 request per 500ms
        const now = Date.now();
        if (this.lastTypingRequest && (now - this.lastTypingRequest) < 500) {
            return;
        }
        this.lastTypingRequest = now;

        if (!this.pusher || !this.currentConversation) return;

        try {
            const response = await fetch('/messages/typing', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    conversation_id: this.currentConversation.id,
                    is_typing: isTyping
                })
            });

            // Don't throw on non-200 responses for typing (non-critical)
            if (!response.ok) {
            }

        } catch (error) {
            // Silent error for typing - non-critical feature
        }
    }

    /**
     * Proactive tick monitoring - ensures all ticks are up to date
     */
    refreshMessageTicks() {
        if (!this.currentConversation || !this.currentConversation.messages) return;

        // Check all own messages and update their tick status if needed
        this.currentConversation.messages.forEach(message => {
            const currentUserId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
            const isOwn = message.user_id == currentUserId;
            
            if (isOwn) {
                // Determine correct tick status
                let correctTickStatus = 'sent';
                if (message.read_at) {
                    correctTickStatus = 'read';
                } else if (message.created_at) {
                    correctTickStatus = 'delivered';
                }
                
                // Check if DOM tick status matches data
                const tickElement = document.querySelector(`[data-message-id="${message.id}"] .message-tick`);
                if (tickElement) {
                    const hasCorrectClass = tickElement.classList.contains(correctTickStatus);
                    if (!hasCorrectClass) {
                        // Update tick status
                        this.updateMessageTick(message.id, correctTickStatus);
                    }
                }
            }
        });
    }

    /**
     * Debounced rendering to prevent rapid re-renders - ENHANCED VERSION
     */
    debouncedRenderMessages() {
        this.needsRender = true;
        
        // Clear existing timeout
        if (this.renderTimeout) {
            clearTimeout(this.renderTimeout);
        }
        
        // Schedule render with minimal delay to batch multiple updates
        this.renderTimeout = setTimeout(() => {
            if (this.needsRender && this.currentConversation) {
                this.renderMessages();
                this.needsRender = false;
            }
            this.renderTimeout = null;
        }, 50); // 50ms debounce - fast enough for good UX, slow enough to batch updates
    }

    /**
     * Immediate render for critical updates (user actions)
     */
    immediateRenderMessages() {
        // Clear any pending debounced renders
        if (this.renderTimeout) {
            clearTimeout(this.renderTimeout);
            this.renderTimeout = null;
        }
        
        this.needsRender = false;
        this.renderMessages();
    }
}

// Widget'ı başlat
let chatWidget;
document.addEventListener('DOMContentLoaded', function() {
    // Sadece giriş yapmış kullanıcılar için chat widget'ını başlat
    if (document.querySelector('meta[name="user-id"]')) {
        chatWidget = new ChatWidget();
        window.chatWidget = chatWidget; // Global erişim için
        
        // Cleanup on page unload to prevent memory leaks
        window.addEventListener('beforeunload', () => {
            if (chatWidget) {
                chatWidget.destroy();
            }
        });
    }
});
