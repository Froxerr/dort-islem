export const elements = {
    chatContainer: document.getElementById('chatContainer'),
    chatToggle: document.getElementById('chatToggle'),
    chatClose: document.getElementById('chatClose'),
    friendsList: document.getElementById('friendsList'),
    chatWindow: document.getElementById('chatWindow'),
    backToFriends: document.getElementById('backToFriends'),
    messagesContainer: document.getElementById('messagesContainer'),
    friendNameContainer: document.getElementById('currentFriendName'),
    friendAvatar: document.getElementById('currentFriendAvatar'),
    messageForm: document.getElementById('messageForm'),
    messageInput: document.getElementById('messageInput'),
    unreadBadge: document.getElementById('unreadBadge'),
};
let currentUserId = null;

const generateColorFromString = (str) => {
    if (!str) return 'hsl(200, 70%, 50%)';
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
        hash = str.charCodeAt(i) + ((hash << 5) - hash);
    }
    const h = hash % 360;
    return `hsl(${h}, 70%, 50%)`;
};

const createMessageElement = (message) => {
    const isOwnMessage = message.user_id == currentUserId;
    const displayName = (message.user && message.user.name) || (message.user && message.user.username) || 'İsimsiz Kullanıcı';
    const avatarInitial = displayName.charAt(0).toUpperCase();
    const avatarColor = generateColorFromString(displayName);

    const messageElement = document.createElement('div');
    messageElement.className = `message ${isOwnMessage ? 'own' : 'other'}`;
    messageElement.dataset.messageId = message.id;
    messageElement.innerHTML = `
        ${!isOwnMessage ? `<div class="message-avatar" style="background-color: ${avatarColor};">${avatarInitial}</div>` : ''}
        <div class="message-bubble">
            <div class="message-content">${message.body}</div>
            <div class="message-footer">
                <span class="message-time">${new Date(message.created_at).toLocaleTimeString('tr-TR', {hour: '2-digit', minute: '2-digit'})}</span>
            </div>
        </div>`;
    return messageElement;
};

export const appendMessage = (message, shouldScroll = true) => {
    const messageElement = createMessageElement(message);
    elements.messagesContainer.appendChild(messageElement);
    if (shouldScroll) {
        elements.messagesContainer.scrollTop = elements.messagesContainer.scrollHeight;
    }
};

export const prependMessages = (messages) => {
    const fragment = document.createDocumentFragment();
    messages.reverse().forEach(message => fragment.appendChild(createMessageElement(message)));
    elements.messagesContainer.prepend(fragment);
};

export const openConversation = (conversation, friendData) => {
    const displayName = friendData.name || 'İsimsiz Kullanıcı';
    const avatarInitial = displayName.charAt(0).toUpperCase();
    const avatarColor = generateColorFromString(displayName);
    elements.friendNameContainer.innerHTML = `<div class="friend-name">${displayName}</div><div class="friend-status">@${friendData.username}</div>`;
    elements.friendAvatar.textContent = avatarInitial;
    elements.friendAvatar.style.backgroundColor = avatarColor;
    elements.messageForm.dataset.conversationId = conversation.id;
    elements.messagesContainer.innerHTML = '';
    if (conversation.messages && conversation.messages.length > 0) {
        const initialMessages = conversation.messages.reverse();
        initialMessages.forEach(message => appendMessage(message, false));
        setTimeout(() => {
            elements.messagesContainer.scrollTop = elements.messagesContainer.scrollHeight;
        }, 0);
    }
    elements.friendsList.style.display = 'none';
    elements.chatWindow.classList.add('active');
};

export const renderFriendsList = (friends) => {
    elements.friendsList.innerHTML = '';
    friends.forEach(friend => {
        const displayName = friend.name || 'İsimsiz Kullanıcı';
        const avatarInitial = displayName.charAt(0).toUpperCase();
        const avatarColor = generateColorFromString(displayName);
        const friendItem = document.createElement('div');
        friendItem.className = 'friend-item';
        friendItem.dataset.friendId = friend.id;
        friendItem.dataset.friendName = friend.name;
        friendItem.dataset.friendUsername = friend.username;
        friendItem.innerHTML = `
            <div class="friend-avatar" style="background-color: ${avatarColor};">${avatarInitial}</div>
            <div class="friend-info">
                <div class="friend-name">${displayName}</div>
                <div class="friend-status">@${friend.username}</div>
            </div>
            ${friend.unread_count > 0 ? `<div class="friend-unread">${friend.unread_count}</div>` : ''}`;
        elements.friendsList.appendChild(friendItem);
    });
};

/**
 * BASİTLEŞTİRİLDİ: "Yazıyor" göstergesini günceller (tek kullanıcı için).
 */
export const updateTypingIndicator = (event) => {
    let typingIndicator = document.querySelector('.typing-indicator');
    if (event.is_typing) {
        if (!typingIndicator) {
            const displayName = event.user.name || 'Birisi';
            typingIndicator = document.createElement('div');
            typingIndicator.className = 'typing-indicator';
            typingIndicator.innerHTML = `<div class="typing-text">${displayName} yazıyor...</div>`;
            elements.messagesContainer.appendChild(typingIndicator);
            elements.messagesContainer.scrollTop = elements.messagesContainer.scrollHeight;
        }
    } else {
        if (typingIndicator) {
            typingIndicator.remove();
        }
    }
};

/**
 * HATA DÜZELTME: Tüm olay dinleyicileri bu tek fonksiyonda toplanmalı.
 */
export const setupUIEventListeners = (onFriendClick, onMessageSend) => {
    currentUserId = document.querySelector('meta[name="user-id"]').getAttribute('content');

    elements.chatToggle.addEventListener('click', () => {
        elements.chatContainer.classList.toggle('active');
        elements.chatToggle.classList.toggle('active');
    });

    elements.chatClose.addEventListener('click', () => {
        elements.chatContainer.classList.remove('active');
        elements.chatToggle.classList.remove('active');
    });

    elements.backToFriends.addEventListener('click', () => {
        elements.friendsList.style.display = 'block';
        elements.chatWindow.classList.remove('active');
    });

    elements.messageForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const body = elements.messageInput.value.trim();
        const conversationId = elements.messageForm.dataset.conversationId;
        if (body && conversationId) {
            onMessageSend(conversationId, body);
            elements.messageInput.value = '';
            elements.messageInput.style.height = 'auto';
        }
    });

    elements.messageInput.addEventListener('input', () => {
        const conversationId = elements.messageForm.dataset.conversationId;
        if (conversationId) {
            const event = new CustomEvent('chat:typing', {
                detail: { conversationId: conversationId }
            });
            window.dispatchEvent(event);
        }
    });

    elements.friendsList.addEventListener('click', (event) => {
        const friendItem = event.target.closest('.friend-item');
        if (friendItem) {
            // Bu, main.js'in arayüzdeki sayacı silebilmesi için gereklidir.
            onFriendClick({
                id: friendItem.dataset.friendId,
                name: friendItem.dataset.friendName === 'null' ? null : friendItem.dataset.friendName,
                username: friendItem.dataset.friendUsername,
            }, friendItem); // 2. parametre olarak tıklanan elementi ekledik.
        }
    });
};

/**
 * Belirtilen arkadaşın okunmamış mesaj sayacını bulur ve 1 artırır.
 * Eğer sayaç yoksa, oluşturur.
 * @param {number} senderId - Mesajı gönderen kullanıcının ID'si
 */
export const incrementFriendUnreadBadge = (senderId) => {
    const friendItem = elements.friendsList.querySelector(`.friend-item[data-friend-id="${senderId}"]`);
    if (!friendItem) return;

    let unreadBadge = friendItem.querySelector('.friend-unread');

    if (!unreadBadge) {
        unreadBadge = document.createElement('div');
        unreadBadge.className = 'friend-unread';
        unreadBadge.textContent = '1';
        // HATA DÜZELTME: Sayacı eklemek için daha garantili bir yöntem.
        // friend-info elementinin sonrasına ekler.
        friendItem.querySelector('.friend-info')?.insertAdjacentElement('afterend', unreadBadge);
    } else {
        const currentCount = parseInt(unreadBadge.textContent, 10) || 0;
        unreadBadge.textContent = currentCount + 1;
    }
};
