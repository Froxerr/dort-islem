import axios from 'axios';

/**
 * Arkadaş listesini sunucudan getirir.
 */
export const getFriends = async () => {
    try {
        const response = await axios.get('/messages/friends');
        return response.data;
    } catch (error) {
        console.error('API isteği başarısız: getFriends', error);
        return [];
    }
};

/**
 * Belirli bir arkadaşla olan sohbeti getirir veya oluşturur.
 * @param {string} friendId - Konuşulacak arkadaşın ID'si
 */
export const getOrCreateConversation = async (friendId) => {
    try {
        // Rotalarımızdaki '/messages/conversation' endpoint'ine istek atıyoruz
        const response = await axios.post('/messages/conversation', { friend_id: friendId });
        return response.data;
    } catch (error) {
        console.error('API isteği başarısız: getOrCreateConversation', error);
        // Hata durumunda null döndürerek hatayı yönetebiliriz
        return null;
    }
};
/**
 * Sunucuya yeni bir mesaj gönderir.
 * @param {number} conversationId - Sohbet ID'si
 * @param {string} body - Mesaj içeriği
 */
export const sendMessage = async (conversationId, body) => {
    try {
        const response = await axios.post('/messages/send', {
            conversation_id: conversationId,
            body: body
        });
        return response.data;
    } catch (error) {
        console.error('API isteği başarısız: sendMessage', error);
        return null;
    }
};

/**
 * Birden fazla mesajı okundu olarak işaretler.
 * @param {Array<number>} messageIds - Okundu olarak işaretlenecek mesaj ID'leri dizisi
 */
export const markMessagesAsRead = async (messageIds) => {
    try {
        // Boş bir dizi göndermemek için kontrol
        if (messageIds.length === 0) return { success: true };

        const response = await axios.post('/messages/mark-read-batch', {
            message_ids: messageIds
        });
        return response.data;
    } catch (error) {
        console.error('API isteği başarısız: markMessagesAsRead', error);
        return null;
    }
};

/**
 * Bir sohbet için belirtilen mesajdan daha eski mesajları sayfa sayfa getirir.
 * @param {number} conversationId
 * @param {number} oldestMessageId - Ekranda görünen en eski mesajın ID'si
 */
export const getOlderMessages = async (conversationId, oldestMessageId) => {
    try {
        const response = await axios.get(`/messages/conversation/${conversationId}`, {
            params: {
                before_message_id: oldestMessageId
            }
        });
        return response.data;
    } catch (error) {
        console.error('API isteği başarısız: getOlderMessages', error);
        return [];
    }
};

/**
 * Gelen eski mesajları sohbet penceresinin en üstüne ekler.
 * @param {Array<object>} messages - Eski mesajlar dizisi
 */
export const prependMessages = (messages) => {
    messages.forEach(message => {
        // appendMessage'ın bir benzeri, ama prepend (üste ekleme) yapıyor
        const isOwnMessage = message.user_id == currentUserId;
        const senderName = (message.user && message.user.name) || (message.user && message.user.username) || 'Bilinmeyen';
        const senderAvatarInitial = senderName ? senderName.charAt(0).toUpperCase() : '?';

        const messageElement = document.createElement('div');
        messageElement.className = `message ${isOwnMessage ? 'own' : 'other'}`;
        // Her mesaja ID'sini verelim ki en eskisini kolayca bulabilelim
        messageElement.dataset.messageId = message.id;

        messageElement.innerHTML = `
            ${!isOwnMessage ? `<div class="message-avatar">${senderAvatarInitial}</div>` : ''}
            <div class="message-bubble">
                <div class="message-content">${message.body}</div>
                <div class="message-footer">
                    <span class="message-time">${new Date(message.created_at).toLocaleTimeString('tr-TR', {hour: '2-digit', minute: '2-digit'})}</span>
                </div>
            </div>
        `;
        // appendChild yerine prepend kullanarak en üste ekliyoruz
        messagesContainer.prepend(messageElement);
    });
};

/**
 * Sunucuya kullanıcının yazma durumunu gönderir.
 * @param {number} conversationId - Sohbet ID'si
 * @param {boolean} isTyping - Kullanıcı yazıyor mu?
 */
export const sendTypingStatus = async (conversationId, isTyping) => {
    console.log(`%c[C] API isteği yapılıyor: sendTypingStatus | Sohbet ID: ${conversationId}, Durum: ${isTyping}`, 'color: #f39c12;');

    try {
        await axios.post('/messages/typing', {
            conversation_id: conversationId,
            is_typing: isTyping
        });
    } catch (error) {
        // Bu isteğin başarısız olması kritik değil, bu yüzden sadece loglayabiliriz.
        console.warn('API isteği başarısız: sendTypingStatus', error);
    }
};
