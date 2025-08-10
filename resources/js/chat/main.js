import * as api from './api.js';
import * as ui from './ui.js';
import * as events from './events.js';

let isLoadingMessages = false;
let hasMoreMessages = true;
let observer;
let currentConversationId = null;
let typingTimer;
const TYPING_TIMER_LENGTH = 1500;
let lastSentTypingState = false;
// Olay dinleyicisi için referans
let newMessageHandler = null;
const handleTyping = (conversationId) => {
    clearTimeout(typingTimer);
    const isInputEmpty = ui.elements.messageInput.value.trim() === '';
    if (!isInputEmpty) {
        if (!lastSentTypingState) {
            api.sendTypingStatus(conversationId, true);
            lastSentTypingState = true;
        }
        typingTimer = setTimeout(() => {
            api.sendTypingStatus(conversationId, false);
            lastSentTypingState = false;
        }, TYPING_TIMER_LENGTH);
    } else {
        if (lastSentTypingState) {
            api.sendTypingStatus(conversationId, false);
            lastSentTypingState = false;
        }
    }
};

async function loadFriends() {
    const friendsListEl = document.querySelector('.friends-list');
    friendsListEl.innerHTML = ''; // temizle

    let friends = [];
    try {
        friends = await api.getFriends(); // mevcut çağrın neyse onu kullan
    } catch (e) {
        // hata durumunda da boş ekran gösterelim
        friends = [];
    }

    if (!friends || friends.length === 0) {
        friendsListEl.appendChild(ui.renderEmptyFriendsState());
        const chatWindow = document.querySelector('.chat-window');
        if (chatWindow) chatWindow.classList.remove('active');
        return;
    }

    ui.renderFriendsList(friends);
}

const disconnectObserver = () => {
    if (observer) {
        observer.disconnect();
        observer = null;
    }
    const existingTrigger = document.getElementById('loadMoreTrigger');
    if (existingTrigger) {
        existingTrigger.remove();
    }
};

const setupInfiniteScroll = () => {
    disconnectObserver();
    if (!hasMoreMessages) return;
    const trigger = document.createElement('div');
    trigger.id = 'loadMoreTrigger';
    ui.elements.messagesContainer.prepend(trigger);
    const onIntersection = async (entries) => {
        const entry = entries[0];
        if (entry.isIntersecting && !isLoadingMessages) {
            isLoadingMessages = true;
            trigger.remove();
            ui.elements.messagesContainer.classList.add('is-loading-more');
            const oldestMessageId = ui.elements.messagesContainer.querySelector('.message')?.dataset.messageId;
            if (!oldestMessageId) {
                isLoadingMessages = false;
                ui.elements.messagesContainer.classList.remove('is-loading-more');
                return;
            }
            const oldScrollHeight = ui.elements.messagesContainer.scrollHeight;
            const olderMessages = await api.getOlderMessages(currentConversationId, oldestMessageId);
            if (olderMessages && olderMessages.length > 0) {
                ui.prependMessages(olderMessages);
                requestAnimationFrame(() => {
                    ui.elements.messagesContainer.scrollTop = ui.elements.messagesContainer.scrollHeight - oldScrollHeight;
                    ui.elements.messagesContainer.classList.remove('is-loading-more');
                    isLoadingMessages = false;
                    setupInfiniteScroll();
                });
            } else {
                hasMoreMessages = false;
                ui.elements.messagesContainer.classList.remove('is-loading-more');
                isLoadingMessages = false;
            }
        }
    };
    observer = new IntersectionObserver(onIntersection, { root: ui.elements.messagesContainer, rootMargin: '200px' });
    observer.observe(trigger);
};


const handleFriendSelection = async (friendData, friendElement) => {
    disconnectObserver();
    isLoadingMessages = false;
    hasMoreMessages = true;
    currentConversationId = null;

    if (friendElement) {
        const friendUnreadBadge = friendElement.querySelector('.friend-unread');
        if (friendUnreadBadge) friendUnreadBadge.remove();
    }

    const conversation = await api.getOrCreateConversation(friendData.id);
    if (!conversation) return;

    currentConversationId = conversation.id;
    ui.openConversation(conversation, friendData);

    // Pusher olaylarını dinle
    events.listenToConversationChannel(
        conversation.id,
        (message) => { ui.appendMessage(message, true); }
    );

    const currentUserId = document.querySelector('meta[name="user-id"]').getAttribute('content');
    const unreadMessageIds = conversation.messages
        .filter(msg => !msg.read_at && msg.user_id != currentUserId)
        .map(msg => msg.id);

    if (unreadMessageIds.length > 0) {
        await api.markMessagesAsRead(unreadMessageIds);
    }

    if (window.chatApp && typeof window.chatApp.updateTotalUnread === 'function') {
        window.chatApp.updateTotalUnread();
    }

    if (conversation.messages.length >= 30) {
        setTimeout(() => { setupInfiniteScroll(); }, 300);
    } else {
        hasMoreMessages = false;
    }
};

const handleSendMessage = async (conversationId, body) => {
    await api.sendMessage(conversationId, body);
};

export const init = async () => {
    ui.setupUIEventListeners(handleFriendSelection, handleSendMessage);

    ui.elements.messageInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            const body = ui.elements.messageInput.value.trim();
            if (body && currentConversationId) {
                handleSendMessage(currentConversationId, body);
                ui.elements.messageInput.value = '';
            }
        }
    });

    const currentUserId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
    if (currentUserId) {
        events.initUserChannelListener(currentUserId, (message) => {
            // Gelen her yeni mesaj için bu fonksiyon çalışacak:

            // 1. Genel sayacı her zaman güncelle (bunu app.js de yapıyor ama burada olması garantidir).
            if (window.chatApp) window.chatApp.updateTotalUnread();

            // 2. Eğer sohbet açık değilse, arkadaş listesindeki sayacı artır.
            if (message.conversation_id != currentConversationId) {
                ui.incrementFriendUnreadBadge(message.user_id);
            }
        });
    }

    try {
        await loadFriends();
        document.getElementById('chatContainer').classList.add('active');
        document.getElementById('chatToggle').classList.add('active');
    } catch (error) {
        console.error("Başlangıçta arkadaş listesi yüklenemedi:", error);
    }
};
