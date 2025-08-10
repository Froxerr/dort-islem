/**
 * Belirli bir sohbet kanalını dinlemeye başlar.
 * @param {number} conversationId - Dinlenecek sohbetin ID'si
 * @param {function} onMessageReceived - Yeni mesaj geldiğinde çalışacak callback fonksiyonu
 * @param {function} onUserTyping - Kullanıcı yazıyor etkinliği geldiğinde çalışacak callback
 */
export const listenToConversationChannel = (conversationId, onMessageReceived) => {
    const channelName = `conversation.${conversationId}`;
    window.Echo.leave(channelName);
    console.log(`Dinleniyor: ${channelName}`);
    window.Echo.private(channelName)
        .listen('.message.sent', (event) => {
            onMessageReceived(event.message);
        });
};

/**
 * Kullanıcının kendi özel kanalını dinler (yeni mesaj bildirimleri için).
 * @param {number} userId - Giriş yapmış kullanıcının ID'si
 * @param {function} onNewMessageNotification - Yeni mesaj geldiğinde çalışacak callback
 */
export const initUserChannelListener = (userId, onNewMessageNotification) => {
    const channelName = `user.${userId}`;
    window.Echo.private(channelName)
        .listen('.message.received', (event) => {
            onNewMessageNotification(event.message);
        });
    console.log(`Dinleniyor: ${channelName} (Genel Bildirimler)`);
};
