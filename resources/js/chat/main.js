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
        if (friendUnreadBadge) {
            friendUnreadBadge.remove();
        }
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

    // Okunmamış mesajları sunucuda işaretle
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
    ui.setupUIEventListeners(
        (friendData, element) => handleFriendSelection(friendData, element),
        handleSendMessage
    );

    // **NİHAİ ÇÖZÜM 2/2: Olay dinleyicisini yeniden düzenle.**
    // Önceki dinleyiciyi (varsa) kaldırarak hafıza sızıntısını ve çift saymayı önle.
    if (newMessageHandler) {
        window.removeEventListener('chat:new-message', newMessageHandler);
    }

    // Yeni dinleyici fonksiyonunu tanımla.
    newMessageHandler = (event) => {
        const message = event.detail.message;
        if (!message) return;

        // Bu kontrol, `currentConversationId`'nin her zaman en güncel değerini kullanır.
        // Bu, "sadece bir kez artıyor" sorununu çözer.
        if (message.conversation_id != currentConversationId) {
            ui.incrementFriendUnreadBadge(message.user_id);
        }
    };

    // Yeni ve temiz dinleyiciyi ekle.
    window.addEventListener('chat:new-message', newMessageHandler);

    try {
        const friends = await api.getFriends();
        ui.renderFriendsList(friends);
        document.getElementById('chatContainer').classList.add('active');
        document.getElementById('chatToggle').classList.add('active');
    } catch (error) {
        console.error("Başlangıçta arkadaş listesi yüklenemedi:", error);
    }
};
