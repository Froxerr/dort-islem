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
        this.typingTimeout = null;
        this.isTyping = false;
        
        this.init();
    }
    
    init() {
        this.createWidget();
        this.initPusher();
        this.attachEventListeners();
        this.loadFriends();
        this.checkUnreadMessages();
        
        // Periyodik olarak okunmamış mesajları kontrol et
        setInterval(() => {
            this.checkUnreadMessages();
        }, 60000); // 30 saniye
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
                            'Content-Type': 'application/json'
                        }
                    }
                });
                console.log('Pusher initialized successfully');
            } else {
                console.warn('Pusher not available. Using polling mode.');
                this.enablePollingMode();
            }
        } catch (error) {
            console.error('Pusher initialization failed:', error);
            this.enablePollingMode();
        }
    }
    
    /**
     * Polling modunu etkinleştir (Pusher yoksa)
     */
    enablePollingMode() {
        this.pusher = null;
        console.log('Chat running in polling mode');
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
                        <div class="loading">🔄 Arkadaşlar yükleniyor...</div>
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
        
        // Enter to send, Shift+Enter for new line
        messageInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage(e);
            }
        });
        
        // Typing indicator
        messageInput.addEventListener('input', () => {
            this.sendTypingStatus(true);
            
            // 2 saniye sonra typing'i durdur
            clearTimeout(this.typingTimeout);
            this.typingTimeout = setTimeout(() => {
                this.sendTypingStatus(false);
            }, 2000);
        });
        
        // Focus çıkınca typing'i durdur
        messageInput.addEventListener('blur', () => {
            this.sendTypingStatus(false);
        });
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
                this.renderFriendsList();
            }
        } catch (error) {
            console.error('Arkadaşlar yüklenirken hata:', error);
            this.showError('Arkadaşlar yüklenemedi');
        }
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
            
            return `
                <div class="friend-item" data-friend-id="${friend.id}" onclick="chatWidget.openChat(${friend.id})">
                    <div class="friend-avatar" style="background: ${friend.profile_image ? 'transparent' : avatarColor}">
                        ${avatarContent}
                    </div>
                    <div class="friend-info">
                        <div class="friend-name">${friend.name || friend.username}</div>
                        <div class="friend-status">🟢 Aktif</div>
                    </div>
                    <div class="friend-unread" style="display: none;">0</div>
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
                this.showChatWindow();
                this.renderMessages();
                this.startMessagePolling();
            }
        } catch (error) {
            console.error('Sohbet açılırken hata:', error);
            this.showError('Sohbet açılamadı');
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
    }
    
    showFriendsList() {
        document.getElementById('friendsList').style.display = 'block';
        document.getElementById('chatWindow').classList.remove('active');
        this.currentConversation = null;
        this.currentFriend = null;
        this.stopMessagePolling();
        this.unsubscribeFromPusherChannel();
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
                <div class="message ${isOwn ? 'own' : 'other'}">
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
        
        // Mesajları okundu olarak işaretle
        this.markMessagesAsRead();
    }
    
    async sendMessage(e) {
        e.preventDefault();
        
        const input = document.getElementById('messageInput');
        const message = input.value.trim();
        
        if (!message || !this.currentConversation) return;
        
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
                const newMessage = await response.json();
                this.currentConversation.messages.push(newMessage);
                this.renderMessages();
                input.value = '';
                input.style.height = 'auto';
            }
        } catch (error) {
            console.error('Mesaj gönderilirken hata:', error);
            this.showError('Mesaj gönderilemedi');
        }
    }
    
    startMessagePolling() {
        if (this.messagePollingInterval) {
            clearInterval(this.messagePollingInterval);
        }
        
        this.messagePollingInterval = setInterval(async () => {
            if (this.currentConversation) {
                await this.refreshMessages();
            }
        }, 3000); // 3 saniye
    }
    
    stopMessagePolling() {
        if (this.messagePollingInterval) {
            clearInterval(this.messagePollingInterval);
            this.messagePollingInterval = null;
        }
    }
    
    async refreshMessages() {
        try {
            const response = await fetch(`/messages/conversation/${this.currentConversation.id}`);
            if (response.ok) {
                const messages = await response.json();
                
                // Yeni mesaj var mı kontrol et
                if (messages.length > this.currentConversation.messages.length) {
                    this.currentConversation.messages = messages;
                    this.renderMessages();
                }
            }
        } catch (error) {
            console.error('Mesajlar yenilenirken hata:', error);
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
            console.error('Okunmamış mesajlar kontrol edilirken hata:', error);
        }
    }
    
    updateUnreadBadge(count) {
        const badge = document.getElementById('unreadBadge');
        this.unreadCount = count;
        
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }
    
    showError(message) {
        console.error(message);
        // Burada toast notification gösterebilirsiniz
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * Sohbetteki okunmamış mesajları okundu olarak işaretle
     */
    async markMessagesAsRead() {
        if (!this.currentConversation || !this.currentConversation.messages) return;
        
        const currentUserId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
        const unreadMessages = this.currentConversation.messages.filter(message => 
            message.user_id != currentUserId && !message.read_at
        );
        
        for (const message of unreadMessages) {
            try {
                await fetch('/messages/mark-read', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ message_id: message.id })
                });
                
                // Mesajı yerel olarak okundu olarak işaretle
                message.read_at = new Date().toISOString();
            } catch (error) {
                console.error('Mesaj okundu olarak işaretlenirken hata:', error);
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
            
            // Geçici test: Public channel dene
            const channelName = `test-channel`;
            console.log('Trying public channel first:', channelName);
            
            this.currentChannel = this.pusher.subscribe(channelName);
            
            this.currentChannel.bind('pusher:subscription_succeeded', () => {
                console.log('✅ Public channel subscription successful!');
                
                // Şimdi private channel'ı dene
                this.subscribeToPrivateChannel();
            });
            
            this.currentChannel.bind('pusher:subscription_error', (error) => {
                console.error('❌ Public channel subscription failed:', error);
                this.startMessagePolling();
            });
            
        } catch (error) {
            console.error('Failed to subscribe to Pusher channel:', error);
            // Fallback to polling
            this.startMessagePolling();
        }
    }
    
    /**
     * Private channel'a subscribe ol
     */
    subscribeToPrivateChannel() {
        console.log('Now trying private channel...');
        console.log('Current conversation:', this.currentConversation);
        console.log('Participants array:', this.currentConversation.participants);
        console.log('Participants length:', this.currentConversation.participants.length);
        console.log('Current user ID:', document.querySelector('meta[name="user-id"]')?.getAttribute('content'));
        
        // Auth debug
        console.log('CSRF Token:', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'));
        console.log('Auth endpoint:', '/broadcasting/auth');
        
        // Manual auth test
        this.testBroadcastAuth();
        this.testGeneralAuth();
        
        // Public channel'dan çık
        if (this.currentChannel) {
            this.pusher.unsubscribe(this.currentChannel.name);
        }
        
        const channelName = `private-conversation.${this.currentConversation.id}`;
        console.log('Private channel name:', channelName);
        
        this.currentChannel = this.pusher.subscribe(channelName);
        
        this.currentChannel.bind('pusher:subscription_succeeded', () => {
            console.log('✅ Private channel subscription successful!');
        });
        
        this.currentChannel.bind('pusher:subscription_error', (error) => {
            console.error('❌ Private channel subscription failed:', error);
            console.log('Falling back to polling mode');
            this.startMessagePolling();
        });
        
        // Yeni mesaj geldiğinde
        this.currentChannel.bind('message.sent', (data) => {
            this.handleNewMessage(data.message);
        });
        
        // Typing indicator
        this.currentChannel.bind('user.typing', (data) => {
            this.handleTypingIndicator(data);
        });
        
                 console.log(`Subscribed to private channel: ${channelName}`);
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
     * Yeni mesajı handle et
     */
    handleNewMessage(message) {
        if (!this.currentConversation || message.conversation_id != this.currentConversation.id) return;
        
        // Mesajı conversation'a ekle
        this.currentConversation.messages.push(message);
        
        // UI'ı güncelle
        this.renderMessages();
        
        // Ses efekti (opsiyonel)
        this.playMessageSound();
        
        // Unread count güncelle
        this.checkUnreadMessages();
    }
    
    /**
     * Typing indicator'ı handle et
     */
    handleTypingIndicator(data) {
        const currentUserId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
        
        // Kendi typing'ımızı gösterme
        if (data.user.id == currentUserId) return;
        
        const typingIndicator = document.getElementById('typingIndicator');
        
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
            if (typingIndicator) {
                typingIndicator.remove();
            }
        }
    }
    
    /**
     * Mesaj ses efekti
     */
    playMessageSound() {
        // Basit bir beep sesi
        try {
            const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmQcBzqF0fS+cSADKIXS8eSNOwcZaLvl6JdKDAhWq+Pwl0wRCkGc3O3UfSQEB3fH8cCbcSUFJIHO8tjAciUFLIHO8diJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmQcBzqF0fS+cSADKIXS8eSNOwcZaLvl6JdKDAhWq+Pwl0wRCkGc3O3UfSQEB3fH8cCbcSUFJIHO8diJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmQcBzqF0fS+cSADKIXS8eSNOwcZaLvl6JdKDAhWq+Pwl0wRCkGc3O3UfSQEB3fH8cCbcSUFJIHO8diJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmQcBzqF0fS+cSADKIXS8eSNOwcZaLvl6JdKDAhWq+Pwl0wRCkGc3O3UfSQEB3fH8cCbcSUFJIHO8diJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmQcBzqF0fS+cSADKIXS8eSNOwcZaLvl6JdKDAhWq+Pwl0wRCkGc3O3UfSQEB3fH8cCbcSUFJIHO8diJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmQcBzqF0fS+cSADKIXS8eSNOwcZaLvl6JdKDAhWq+Pwl0wRCkGc3O3UfSQEB3fH8cCbcSUFJIHO8diJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmQcBzqF0fS+cSADKIXS8eSNOwcZaLvl6JdKDAhWq+Pwl0wRCkGc3O3UfSQEB3fH8cCbcSUFJIHO8diJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmQcBzqF0fS+cSADKIXS8eSNOwcZaLvl6JdKDAhWq+Pwl0wRCkGc3O3UfSQEB3fH8cCbcSUFJIHO8diJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmQcBzqF0fS+cSADKIXS8eSNOwcZaLvl6JdKDAhWq+Pwl0wRCkGc3O3UfSQEB3fH8cCbcSUFJIHO8diJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmQcBzqF0fS+cSADKIXS8eSNOwcZaLvl6JdKDAhWq+Pwl0wRCkGc3O3UfSQEB3fH8cCbcSUFJIHO8diJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmQcBzqF0fS+cSADKIXS8eSNOwcZaLvl6JdKDAhWq+Pwl0wRCkGc3O3UfSQEB3fH8cCbcSUFJIHO8diJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmQcBzqF0fS+cSADKIXS8eSNOwcZaLvl6JdKDAhWq+Pwl0wRCkGc3O3UfSQEB3fH8cCb');
            audio.volume = 0.3;
            audio.play().catch(() => {
                // Ses çalınmazsa sessizce devam et
            });
        } catch (error) {
            // Ses hatası varsa logla ama durduramaz
        }
    }
    
    /**
     * Manual broadcast auth test
     */
    async testBroadcastAuth() {
        console.log('🧪 Testing broadcast auth manually...');
        
        try {
            const response = await fetch('/broadcasting/auth', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `channel_name=private-conversation.${this.currentConversation.id}&socket_id=test123`
            });
            
            console.log('📊 Manual auth response:', {
                status: response.status,
                statusText: response.statusText,
                headers: Object.fromEntries(response.headers.entries())
            });
            
            if (response.ok) {
                const data = await response.text();
                console.log('✅ Manual auth success:', data);
            } else {
                const errorText = await response.text();
                console.log('❌ Manual auth failed:', errorText);
            }
        } catch (error) {
            console.error('🚫 Manual auth error:', error);
        }
    }
    
    /**
     * Test general authentication
     */
    async testGeneralAuth() {
        console.log('🧪 Testing general auth...');
        
        try {
            const response = await fetch('/test-broadcast-auth', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({test: 'data'})
            });
            
            if (response.ok) {
                const data = await response.json();
                console.log('✅ General auth test success:', data);
            } else {
                console.log('❌ General auth test failed:', response.status);
            }
        } catch (error) {
            console.error('🚫 General auth test error:', error);
        }
    }
    
    /**
     * Typing durumunu gönder
     */
    async sendTypingStatus(isTyping) {
        // Pusher yoksa typing indicator kullanma
        if (!this.pusher || !this.currentConversation || this.isTyping === isTyping) return;
        
        this.isTyping = isTyping;
        
        try {
            await fetch('/messages/typing', {
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
        } catch (error) {
            console.error('Typing status gönderilirken hata:', error);
        }
    }
}

// Widget'ı başlat
let chatWidget;
document.addEventListener('DOMContentLoaded', function() {
    // Sadece giriş yapmış kullanıcılar için chat widget'ını başlat
    if (document.querySelector('meta[name="user-id"]')) {
        chatWidget = new ChatWidget();
    }
}); 