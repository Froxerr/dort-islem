import './bootstrap';
import axios from 'axios';

// --- GENEL BİLDİRİM DİNLEYİCİSİ ---
const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
const unreadBadge = document.getElementById('unreadBadge');

const fetchAndUpdateTotalUnread = async () => {
    // unreadBadge elementi her sayfada olmayabilir, kontrol edelim.
    if (!unreadBadge) return;
    try {
        const total = await axios.get('/messages/unread-total').then(res => res.data.count);
        if (total > 0) {
            unreadBadge.textContent = total > 9 ? '9+' : total;
            unreadBadge.style.display = 'grid';
        } else {
            unreadBadge.style.display = 'none';
        }
    } catch (error) { console.error('Toplam okunmamış sayısı alınamadı.', error); }
};
window.chatApp = {
    updateTotalUnread: fetchAndUpdateTotalUnread
};

if (userId) {
    fetchAndUpdateTotalUnread();
    window.Echo.private(`user.${userId}`)
        .listen('.message.received', (event) => {
            fetchAndUpdateTotalUnread();

            // **TEST 1: Sadece anons yap ve logla.**
            console.log("%c[app.js] TEST 1: Yeni mesaj anonsu yapılıyor...", "background: #f1c40f; color: #000;");
            window.dispatchEvent(new CustomEvent('chat:new-message', {
                detail: { message: event.message }
            }));
        });
}

// --- LAZY LOADING KISMI ---
const chatToggleButton = document.getElementById('chatToggle');
if (chatToggleButton) {
    let isChatModuleLoaded = false;
    chatToggleButton.addEventListener('click', async () => {
        if (isChatModuleLoaded) return;
        try {
            const chatModule = await import('./chat/main.js');
            chatModule.init();
            isChatModuleLoaded = true;
        } catch (error) {
            console.error("Sohbet modülü yüklenemedi.", error);
        }
    });
}
