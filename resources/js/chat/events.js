/**
 * Belirli bir sohbet kanalını dinlemeye başlar.
 * @param {number} conversationId - Dinlenecek sohbetin ID'si
 * @param {function} onMessageReceived - Yeni mesaj geldiğinde çalışacak callback fonksiyonu
 * @param {function} onUserTyping - Kullanıcı yazıyor etkinliği geldiğinde çalışacak callback
 */
export const listenToConversationChannel = (conversationId, onMessageReceived, onUserTyping) => {
    const channelName = `conversation.${conversationId}`;

    // Aynı kanala tekrar tekrar bağlanmayı önlemek için mevcut dinleyicileri temizle
    window.Echo.leave(channelName);

    console.log(`Dinleniyor: ${channelName}`);


    window.Echo.private(channelName)
        .listen('.message.sent', (event) => {
            console.log('Yeni mesaj alındı (Pusher):', event.message);
            onMessageReceived(event.message);
        })
        // YENİ: "Yazıyor..." olayını dinle
        .listen('.user.typing', (event) => {
            console.log(`%c[D] Echo olayı alındı: .user.typing | Gelen veri:`, 'color: #2ecc71; font-weight: bold;', event);

            onUserTyping(event);
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
            console.log('Genel bildirim alındı:', event);
            // Yeni mesaj geldiğinde ana modülü haberdar et
            onNewMessageNotification(event.message);
        });
    console.log(`Dinleniyor: ${channelName} (Genel Bildirimler)`);
};
