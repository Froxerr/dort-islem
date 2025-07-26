// Arkadaş Sistemi JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Tab sistemi
    initializeTabs();
    
    // CSRF token'ı al
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // Canlı arama
    setupLiveSearch();
});

// Tab sistemi
function initializeTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.dataset.tab;
            
            // Aktif tab'ı kaldır
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Yeni tab'ı aktif et
            this.classList.add('active');
            document.getElementById(targetTab + '-content').classList.add('active');
        });
    });
}

// Arkadaşlık isteği gönder
function sendFriendRequest(userId) {
    if (!confirm('Bu kullanıcıya arkadaşlık isteği göndermek istediğinizden emin misiniz?')) {
        return;
    }
    
    fetch('/profile/friends/send-request', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            user_id: userId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            // Butonu güncelle
            updateButtonState(userId, 'pending_sent');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Bir hata oluştu. Lütfen tekrar deneyin.', 'error');
    });
}

// Arkadaşı kaldır
async function removeFriend(friendId) {
    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!token) throw new Error('CSRF token bulunamadı!');

        const response = await fetch('/profile/friends/remove-friend', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                user_id: friendId  // friend_id yerine user_id kullanıyoruz
            })
        });

        // Response JSON değilse hata fırlat
        if (!response.ok) {
            const text = await response.text();
            console.error('Server response:', text);
            throw new Error(text ? JSON.parse(text).message : 'Sunucu hatası: ' + response.status);
        }

        const data = await response.json();

        if (data.success) {
            location.reload();
        } else {
            throw new Error(data.message || 'Bir hata oluştu');
        }
    } catch (error) {
        console.error('Error:', error);
        alert(error.message || 'Bir hata oluştu. Lütfen tekrar deneyin.');
    }
}

// Kullanıcıyı engelle
function blockUser(userId) {
    if (!confirm('Bu kullanıcıyı engellemek istediğinizden emin misiniz?')) {
        return;
    }
    
    fetch('/profile/friends/block-user', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            user_id: userId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            // Sayfayı yenile
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Bir hata oluştu. Lütfen tekrar deneyin.', 'error');
    });
}

// Arama sayfasında bekleyen isteği kabul et
function acceptPendingRequest(userId) {
    // Önce bekleyen isteği bul
    fetch('/profile/friends/accept-request', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            user_id: userId // Bu durumda user_id ile bulacağız
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            updateButtonState(userId, 'friends');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Bir hata oluştu. Lütfen tekrar deneyin.', 'error');
    });
}

// Buton durumunu güncelle
function updateButtonState(userId, status) {
    const userCard = document.querySelector(`[data-user-id="${userId}"]`);
    if (!userCard) return;
    
    const actionButton = userCard.querySelector('.btn-add, .btn-pending, .btn-accept, .btn-friends');
    if (!actionButton) return;
    
    // Eski class'ları temizle
    actionButton.classList.remove('btn-add', 'btn-pending', 'btn-accept', 'btn-friends');
    
    switch (status) {
        case 'pending_sent':
            actionButton.classList.add('btn-pending');
            actionButton.innerHTML = '<i class="fas fa-clock"></i> Gönderildi';
            actionButton.disabled = true;
            break;
        case 'friends':
            actionButton.classList.add('btn-friends');
            actionButton.innerHTML = '<i class="fas fa-check"></i> Arkadaş';
            actionButton.disabled = true;
            break;
        case 'none':
            actionButton.classList.add('btn-add');
            actionButton.innerHTML = '<i class="fas fa-user-plus"></i> Ekle';
            actionButton.disabled = false;
            break;
    }
}

// Bildirim göster
function showNotification(message, type = 'info') {
    // Toastify varsa kullan
    if (typeof Toastify !== 'undefined') {
        const backgroundColor = {
            'success': '#4CAF50',
            'error': '#f44336',
            'warning': '#ff9800',
            'info': '#2196F3'
        };
        
        Toastify({
            text: message,
            duration: 3000,
            gravity: "top",
            position: "right",
            backgroundColor: backgroundColor[type] || backgroundColor['info'],
            stopOnFocus: true
        }).showToast();
    } else {
        // Fallback: basit alert
        alert(message);
    }
}

// Arama input'u için debounce
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Canlı arama
function setupLiveSearch() {
    const searchInput = document.querySelector('.search-input');
    const searchResults = document.querySelector('.search-results');
    const suggestedFriends = document.querySelector('.suggested-friends');
    
    if (!searchInput) return;
    
    const debouncedSearch = debounce(function(query) {
        if (query.length >= 2) {
            performSearch(query);
        } else if (suggestedFriends) {
            // Arama boşsa önerileri göster
            searchResults.style.display = 'none';
            suggestedFriends.style.display = 'block';
        }
    }, 300);
    
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        if (query.length >= 2) {
            if (suggestedFriends) {
                suggestedFriends.style.display = 'none';
            }
            searchResults.style.display = 'block';
            debouncedSearch(query);
        } else {
            if (suggestedFriends) {
                suggestedFriends.style.display = 'block';
            }
            searchResults.style.display = 'none';
        }
    });
}

// Arama gerçekleştir
function performSearch(query) {
    const searchResults = document.querySelector('.search-results');
    const loadingIndicator = document.createElement('div');
    loadingIndicator.className = 'loading-indicator';
    loadingIndicator.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Aranıyor...';
    
    // Mevcut sonuçları temizle ve yükleniyor göster
    if (searchResults) {
        searchResults.innerHTML = '';
        searchResults.appendChild(loadingIndicator);
    }
    
    fetch(`/profile/friends/search?q=${encodeURIComponent(query)}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.text())
    .then(html => {
        if (searchResults) {
            // Yükleniyor göstergesini kaldır ve sonuçları göster
            searchResults.innerHTML = html;
            
            // Sonuçları yumuşak bir şekilde göster
            const resultItems = searchResults.querySelectorAll('.user-card');
            resultItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    item.style.transition = 'all 0.3s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, index * 100);
            });
        }
    })
    .catch(error => {
        console.error('Search error:', error);
        if (searchResults) {
            searchResults.innerHTML = `
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    Arama sırasında bir hata oluştu.
                </div>
            `;
        }
    });
}

// Sayfa yüklendiğinde canlı aramayı başlat
document.addEventListener('DOMContentLoaded', function() {
    setupLiveSearch();
}); 

// Modal işlemleri
let currentRequestId = null;
let currentFriendId = null;
const acceptModal = document.getElementById('acceptRequestModal');
const rejectModal = document.getElementById('rejectRequestModal');
// Arkadaş silme modalı için değişkenler
const removeFriendModal = document.getElementById('removeFriendModal');
const closeModalButtons = document.querySelectorAll('.close-modal');
const confirmRemoveButton = document.getElementById('confirmRemoveFriend');
let friendToRemove = null;
const confirmAcceptButton = document.getElementById('confirmAcceptRequest');
const confirmRejectButton = document.getElementById('confirmRejectRequest');
const confirmAcceptFriendButton = document.getElementById('acceptRequestModal');


// Kabul modalını göster
function showAcceptModal(userName, requestId) {
    document.getElementById('requestSenderName').textContent = userName;
    currentRequestId = requestId;
    acceptModal.style.display = 'block';
}

// Reddet modalını göster
function showRejectModal(userName, requestId) {
    document.getElementById('rejectRequestSenderName').textContent = userName;
    currentRequestId = requestId;
    rejectModal.style.display = 'block';
}

// Arkadaş silme modalını göster
function showRemoveFriendModal(friendId, friendName) {
    friendToRemove = friendId;
    document.getElementById('removeFriendName').textContent = friendName;
    removeFriendModal.style.display = 'block';
}

// Modal kapatma
function closeRemoveModal() {
    removeFriendModal.style.display = 'none';
    friendToRemove = null;
}

// Modalları kapat
function closeModals() {
    acceptModal.style.display = 'none';
    rejectModal.style.display = 'none';
    removeFriendModal.style.display = 'none';
    currentRequestId = null;
    currentFriendId = null;
}

// Kapatma butonları için event listener
closeModalButtons.forEach(button => {
    button.addEventListener('click', closeModals);
});

// Modal dışına tıklandığında kapat
window.addEventListener('click', function(e) {
    if (e.target == acceptModal || e.target == rejectModal || e.target == removeFriendModal) {
        closeModals();
    }
});

// İsteği kabul et
confirmAcceptButton.addEventListener('click', function() {
    if (currentRequestId) {
        acceptRequest(currentRequestId);
    }
});

// İsteği reddet
confirmRejectButton.addEventListener('click', function() {
    if (currentRequestId) {
        rejectRequest(currentRequestId);
    }
});

// Arkadaşı kaldır butonu için event listener
confirmRemoveButton.addEventListener('click', function() {
    if (friendToRemove) {
        removeFriend(friendToRemove);
        closeRemoveModal();
    }
});

// Arkadaşlık isteğini kabul et
async function acceptRequest(requestId) {
    const button = document.getElementById('confirmAcceptRequest');
    button.disabled = true;
    
    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!token) throw new Error('CSRF token bulunamadı!');

        const response = await fetch('/profile/friends/accept-request', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({ friendship_id: requestId })
        });

        const data = await response.json();

        if (data.success) {
            // Modal'ı kapat
            closeModals();
            // Sayfayı yenile
            location.reload();
        } else {
            throw new Error(data.message || 'Bir hata oluştu');
        }
    } catch (error) {
        alert(error.message);
    } finally {
        button.disabled = false;
    }
}

// Arkadaşlık isteğini reddet
async function rejectRequest(requestId) {
    const button = document.getElementById('confirmRejectRequest');
    button.disabled = true;

    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!token) throw new Error('CSRF token bulunamadı!');

        const response = await fetch('/profile/friends/reject-request', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({ friendship_id: requestId })
        });

        const data = await response.json();

        if (data.success) {
            // Modal'ı kapat
            closeModals();
            // Sayfayı yenile
            location.reload();
        } else {
            throw new Error(data.message || 'Bir hata oluştu');
        }
    } catch (error) {
        alert(error.message);
    } finally {
        button.disabled = false;
    }
}

// Boş durum kontrolü
function checkEmptyState() {
    const requestsList = document.querySelector('.requests-list');
    if (requestsList && requestsList.children.length === 0) {
        requestsList.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>Bekleyen istek yok</p>
            </div>
        `;
    }
} 
